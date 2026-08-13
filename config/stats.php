<?php
// ============================================================
// Fonctions statistiques pour l'analyse des études de marché
// ============================================================

// --- Statistiques descriptives ---

function moyenne($values) {
    $values = array_filter($values, fn($v) => $v !== null && $v !== '');
    if (count($values) == 0) return 0;
    return array_sum($values) / count($values);
}

function mediane($values) {
    $values = array_filter($values, fn($v) => $v !== null && $v !== '');
    sort($values);
    $n = count($values);
    if ($n == 0) return 0;
    if ($n % 2 == 0) {
        return ($values[$n / 2 - 1] + $values[$n / 2]) / 2;
    }
    return $values[(int)($n / 2)];
}

function ecartType($values) {
    $values = array_filter($values, fn($v) => $v !== null && $v !== '');
    $n = count($values);
    if ($n < 2) return 0;
    $moy = moyenne($values);
    $somme_carres = 0;
    foreach ($values as $v) {
        $somme_carres += pow($v - $moy, 2);
    }
    return sqrt($somme_carres / ($n - 1));
}

function variance($values) {
    return pow(ecartType($values), 2);
}

function minimum($values) {
    $values = array_filter($values, fn($v) => $v !== null && $v !== '');
    if (count($values) == 0) return 0;
    return min($values);
}

function maximum($values) {
    $values = array_filter($values, fn($v) => $v !== null && $v !== '');
    if (count($values) == 0) return 0;
    return max($values);
}

function quartiles($values) {
    $values = array_filter($values, fn($v) => $v !== null && $v !== '');
    sort($values);
    $n = count($values);
    if ($n == 0) return [0, 0, 0];
    $q1 = $values[(int)($n * 0.25)];
    $q2 = mediane($values);
    $q3 = $values[(int)($n * 0.75)];
    return [$q1, $q2, $q3];
}

// --- Tris à plat : effectifs et pourcentages ---

function triAPlat($reponses) {
    $effectifs = array_count_values($reponses);
    ksort($effectifs);
    $total = array_sum($effectifs);
    $result = [];
    foreach ($effectifs as $valeur => $eff) {
        $result[] = [
            'valeur' => $valeur,
            'effectif' => $eff,
            'pourcentage' => $total > 0 ? ($eff / $total) * 100 : 0,
        ];
    }
    return ['data' => $result, 'total' => $total];
}

// --- Tris croisés : tableaux de contingence ---

function triCroise($var1, $var2) {
    $lignes = array_unique($var1);
    $colonnes = array_unique($var2);
    sort($lignes);
    sort($colonnes);
    $tableau = [];
    foreach ($lignes as $l) {
        foreach ($colonnes as $c) {
            $tableau[$l][$c] = 0;
        }
    }
    for ($i = 0; $i < count($var1); $i++) {
        if (isset($var1[$i]) && isset($var2[$i])) {
            $tableau[$var1[$i]][$var2[$i]]++;
        }
    }
    return [
        'lignes' => $lignes,
        'colonnes' => $colonnes,
        'tableau' => $tableau,
    ];
}

// --- Test du Khi² : χ² = Σ (O − E)² / E ---

function testKhi2($tableau_contingence, $lignes, $colonnes) {
    $nb_lignes = count($lignes);
    $nb_colonnes = count($colonnes);
    $total_general = 0;
    $totaux_lignes = [];
    $totaux_colonnes = [];

    foreach ($lignes as $l) {
        $totaux_lignes[$l] = 0;
        foreach ($colonnes as $c) {
            $totaux_lignes[$l] += $tableau_contingence[$l][$c];
        }
        $total_general += $totaux_lignes[$l];
    }
    foreach ($colonnes as $c) {
        $totaux_colonnes[$c] = 0;
        foreach ($lignes as $l) {
            $totaux_colonnes[$c] += $tableau_contingence[$l][$c];
        }
    }

    $khi2 = 0;
    $tableau_attendu = [];
    foreach ($lignes as $l) {
        foreach ($colonnes as $c) {
            $attendu = ($totaux_lignes[$l] * $totaux_colonnes[$c]) / $total_general;
            $tableau_attendu[$l][$c] = $attendu;
            $observe = $tableau_contingence[$l][$c];
            if ($attendu > 0) {
                $khi2 += pow($observe - $attendu, 2) / $attendu;
            }
        }
    }

    $ddl = ($nb_lignes - 1) * ($nb_colonnes - 1);
    $p_value = khi2PValue($khi2, $ddl);

    // V de Cramer
    $v_cramer = $total_general > 0 ? sqrt($khi2 / ($total_general * (min($nb_lignes, $nb_colonnes) - 1))) : 0;

    return [
        'khi2' => $khi2,
        'ddl' => $ddl,
        'p_value' => $p_value,
        'v_cramer' => $v_cramer,
        'tableau_attendu' => $tableau_attendu,
        'totaux_lignes' => $totaux_lignes,
        'totaux_colonnes' => $totaux_colonnes,
        'total_general' => $total_general,
    ];
}

// Approximation de la p-value du Khi² (méthode de Wilson-Hilferty)
function khi2PValue($khi2, $ddl) {
    if ($ddl <= 0) return 1;
    if ($khi2 <= 0) return 1;
    // Approximation de Wilson-Hilferty
    $x = 1 - 2 / (9 * $ddl);
    $y = sqrt(2 / (9 * $ddl));
    $z = ($khi2 / $ddl) ** (1/3);
    $z = ($z - $x) / $y;
    // CDF de la loi normale standard
    $p = 1 - normaleCDF($z);
    return max(0, min(1, $p));
}

// CDF de la loi normale standard
function normaleCDF($z) {
    $t = 1 / (1 + 0.2316419 * abs($z));
    $d = 0.3989423 * exp(-$z * $z / 2);
    $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
    if ($z > 0) return 1 - $p;
    return $p;
}

// --- Test t de Student (comparaison de deux moyennes) ---

function testTStudent($groupe1, $groupe2) {
    $n1 = count($groupe1);
    $n2 = count($groupe2);
    if ($n1 < 2 || $n2 < 2) return null;

    $m1 = moyenne($groupe1);
    $m2 = moyenne($groupe2);
    $s1 = ecartType($groupe1);
    $s2 = ecartType($groupe2);

    // Variance poolée
    $sp2 = (($n1 - 1) * $s1 * $s1 + ($n2 - 1) * $s2 * $s2) / ($n1 + $n2 - 2);
    $sp = sqrt($sp2);

    $t = $sp > 0 ? ($m1 - $m2) / ($sp * sqrt(1 / $n1 + 1 / $n2)) : 0;
    $ddl = $n1 + $n2 - 2;

    // P-value approximative (bilatérale)
    $p_value = 2 * (1 - normaleCDF(abs($t)));

    return [
        't' => $t,
        'ddl' => $ddl,
        'p_value' => $p_value,
        'moyenne1' => $m1,
        'moyenne2' => $m2,
        'ecart_type1' => $s1,
        'ecart_type2' => $s2,
        'n1' => $n1,
        'n2' => $n2,
        'difference' => $m1 - $m2,
    ];
}

// --- ANOVA (analyse de variance à un facteur) ---

function anova($groupes) {
    $k = count($groupes);
    if ($k < 2) return null;

    $toutes_valeurs = [];
    $n_total = 0;
    $moyennes_groupes = [];
    $n_groupes = [];

    foreach ($groupes as $groupe) {
        $n = count($groupe);
        if ($n == 0) continue;
        $moyennes_groupes[] = moyenne($groupe);
        $n_groupes[] = $n;
        $n_total += $n;
        $toutes_valeurs = array_merge($toutes_valeurs, $groupe);
    }

    if ($n_total == 0 || $k < 2) return null;

    $grande_moyenne = moyenne($toutes_valeurs);

    // Somme des carrés inter-groupes (SSB)
    $ssb = 0;
    for ($i = 0; $i < $k; $i++) {
        if (isset($n_groupes[$i]) && $n_groupes[$i] > 0) {
            $ssb += $n_groupes[$i] * pow($moyennes_groupes[$i] - $grande_moyenne, 2);
        }
    }

    // Somme des carrés intra-groupes (SSW)
    $ssw = 0;
    foreach ($groupes as $i => $groupe) {
        foreach ($groupe as $val) {
            if (isset($moyennes_groupes[$i])) {
                $ssw += pow($val - $moyennes_groupes[$i], 2);
            }
        }
    }

    $ddl_inter = $k - 1;
    $ddl_intra = $n_total - $k;

    $msb = $ddl_inter > 0 ? $ssb / $ddl_inter : 0;
    $msw = $ddl_intra > 0 ? $ssw / $ddl_intra : 0;

    $F = $msw > 0 ? $msb / $msw : 0;
    $p_value = 1 - fisherCDF($F, $ddl_inter, $ddl_intra);

    return [
        'F' => $F,
        'ddl_inter' => $ddl_inter,
        'ddl_intra' => $ddl_intra,
        'p_value' => $p_value,
        'ssb' => $ssb,
        'ssw' => $ssw,
        'msb' => $msb,
        'msw' => $msw,
        'grande_moyenne' => $grande_moyenne,
        'moyennes_groupes' => $moyennes_groupes,
        'n_groupes' => $n_groupes,
    ];
}

// Approximation CDF de Fisher
function fisherCDF($F, $d1, $d2) {
    if ($F <= 0) return 0;
    $x = $d2 / ($d2 + $d1 * $F);
    return 1 - betaIncomplete(0, $x, $d2 / 2, $d1 / 2);
}

// Fonction béta incomplète (approximation par série)
function betaIncomplete($a, $x, $p, $q) {
    if ($x <= 0) return 0;
    if ($x >= 1) return 1;
    // Approximation par série de continued fraction
    $lbeta = logGamma($p) + logGamma($q) - logGamma($p + $q);
    $front = exp($p * log($x) + ($q - 1) * log(1 - $x) - $lbeta) / $p;
    return $front * betaCF($x, $p, $q);
}

function betaCF($x, $a, $b) {
    $max_iter = 200;
    $eps = 1e-10;
    $qab = $a + $b;
    $qap = $a + 1;
    $qam = $a - 1;
    $c = 1;
    $d = 1 - $qab * $x / $qap;
    if (abs($d) < $eps) $d = $eps;
    $d = 1 / $d;
    $h = $d;
    for ($m = 1; $m <= $max_iter; $m++) {
        $m2 = 2 * $m;
        $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
        $d = 1 + $aa * $d;
        if (abs($d) < $eps) $d = $eps;
        $c = 1 + $aa / $c;
        if (abs($c) < $eps) $c = $eps;
        $d = 1 / $d;
        $h *= $d * $c;
        $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
        $d = 1 + $aa * $d;
        if (abs($d) < $eps) $d = $eps;
        $c = 1 + $aa / $c;
        if (abs($c) < $eps) $c = $eps;
        $d = 1 / $d;
        $del = $d * $c;
        $h *= $del;
        if (abs($del - 1) < $eps) break;
    }
    return $h;
}

function logGamma($x) {
    $coeff = [76.18009172947146, -86.50532032941677, 24.01409824083091, -1.231739572450155, 0.1208650973866179e-2, -0.5395239384953e-5];
    $y = $x;
    $tmp = $x + 5.5;
    $tmp -= ($x + 0.5) * log($tmp);
    $ser = 1.000000000190015;
    for ($j = 0; $j < 6; $j++) {
        $y += 1;
        $ser += $coeff[$j] / $y;
    }
    return -$tmp + log(2.5066282746310005 * $ser / $x);
}

// --- Corrélation de Pearson ---

function correlationPearson($x, $y) {
    $n = count($x);
    if ($n < 2) return null;

    $mx = moyenne($x);
    $my = moyenne($y);

    $num = 0;
    $denx = 0;
    $deny = 0;
    for ($i = 0; $i < $n; $i++) {
        $dx = $x[$i] - $mx;
        $dy = $y[$i] - $my;
        $num += $dx * $dy;
        $denx += $dx * $dx;
        $deny += $dy * $dy;
    }

    $den = sqrt($denx * $deny);
    if ($den == 0) return null;

    $r = $num / $den;
    // Test de significativité
    $t = $n > 2 ? $r * sqrt(($n - 2) / (1 - $r * $r)) : 0;
    $p_value = 2 * (1 - normaleCDF(abs($t)));

    return [
        'r' => $r,
        'n' => $n,
        't' => $t,
        'p_value' => $p_value,
    ];
}

// --- Corrélation de Spearman ---

function correlationSpearman($x, $y) {
    $n = count($x);
    if ($n < 2) return null;

    $rx = calculerRangs($x);
    $ry = calculerRangs($y);

    return correlationPearson($rx, $ry);
}

function calculerRangs($values) {
    $n = count($values);
    $indexed = [];
    for ($i = 0; $i < $n; $i++) {
        $indexed[$i] = $values[$i];
    }
    asort($indexed);
    $rangs = [];
    $rang = 1;
    $prev_val = null;
    $count_ties = 0;
    $start_tie = 0;
    foreach ($indexed as $idx => $val) {
        if ($val === $prev_val) {
            $count_ties++;
        } else {
            if ($count_ties > 0) {
                $moy_rang = ($rang - $count_ties - 1 + $rang - 1) / 2;
                for ($j = $start_tie; $j < $rang - 1; $j++) {
                    $keys = array_keys($indexed);
                    $rangs[$keys[$j]] = $moy_rang;
                }
            }
            $count_ties = 0;
            $start_tie = $rang - 1;
        }
        $rangs[$idx] = $rang;
        $prev_val = $val;
        $rang++;
    }
    if ($count_ties > 0) {
        $moy_rang = ($rang - $count_ties - 1 + $rang - 1) / 2;
        for ($j = $start_tie; $j < $rang - 1; $j++) {
            $keys = array_keys($indexed);
            if (isset($keys[$j])) $rangs[$keys[$j]] = $moy_rang;
        }
    }
    ksort($rangs);
    return array_values($rangs);
}

// --- Analyse en Composantes Principales (ACP) simplifiée ---

function acp($data, $nb_composantes = 2) {
    // $data : tableau de tableaux [individu => [var1, var2, ...]]
    $n = count($data);
    if ($n < 2) return null;
    $p = count($data[0]);
    if ($p < 2) return null;

    // Centrer et réduire
    $moyennes = [];
    $ecarts = [];
    for ($j = 0; $j < $p; $j++) {
        $col = array_column($data, $j);
        $moyennes[$j] = moyenne($col);
        $ecarts[$j] = ecartType($col);
        if ($ecarts[$j] == 0) $ecarts[$j] = 1;
    }

    $data_centree = [];
    foreach ($data as $i => $row) {
        $row_centre = [];
        for ($j = 0; $j < $p; $j++) {
            $row_centre[] = ($row[$j] - $moyennes[$j]) / $ecarts[$j];
        }
        $data_centree[] = $row_centre;
    }

    // Matrice de corrélation
    $mat_corr = [];
    for ($i = 0; $i < $p; $i++) {
        for ($j = 0; $j < $p; $j++) {
            $col_i = array_column($data_centree, $i);
            $col_j = array_column($data_centree, $j);
            $mat_corr[$i][$j] = array_sum(array_map(fn($a, $b) => $a * $b, $col_i, $col_j)) / ($n - 1);
        }
    }

    // Valeurs propres et vecteurs propres par méthode des puissances
    $valeurs_propres = [];
    $vecteurs_propres = [];
    $mat_travail = $mat_corr;

    for ($k = 0; $k < min($nb_composantes, $p); $k++) {
        $result = puissanceIteree($mat_travail, $p);
        $valeurs_propres[] = $result['valeur'];
        $vecteurs_propres[] = $result['vecteur'];

        // Déflation : retirer la composante trouvée
        for ($i = 0; $i < $p; $i++) {
            for ($j = 0; $j < $p; $j++) {
                $mat_travail[$i][$j] -= $result['valeur'] * $result['vecteur'][$i] * $result['vecteur'][$j];
            }
        }
    }

    // Pourcentage de variance expliquée
    $somme_vp = array_sum($valeurs_propres);
    $variance_expliquee = [];
    foreach ($valeurs_propres as $vp) {
        $variance_expliquee[] = $somme_vp > 0 ? ($vp / $p) * 100 : 0;
    }

    // Coordonnées des individus sur les composantes
    $coordonnees = [];
    foreach ($data_centree as $i => $row) {
        $coords = [];
        for ($k = 0; $k < count($vecteurs_propres); $k++) {
            $coord = 0;
            for ($j = 0; $j < $p; $j++) {
                $coord += $row[$j] * $vecteurs_propres[$k][$j];
            }
            $coords[] = $coord;
        }
        $coordonnees[] = $coords;
    }

    return [
        'valeurs_propres' => $valeurs_propres,
        'variance_expliquee' => $variance_expliquee,
        'vecteurs_propres' => $vecteurs_propres,
        'coordonnees' => $coordonnees,
        'nb_variables' => $p,
        'nb_individus' => $n,
    ];
}

function puissanceIteree($matrice, $taille, $max_iter = 100, $eps = 1e-8) {
    // Vecteur initial
    $v = array_fill(0, $taille, 1 / sqrt($taille));
    $valeur = 0;

    for ($iter = 0; $iter < $max_iter; $iter++) {
        // Multiplication matrice * vecteur
        $v_new = array_fill(0, $taille, 0);
        for ($i = 0; $i < $taille; $i++) {
            for ($j = 0; $j < $taille; $j++) {
                $v_new[$i] += $matrice[$i][$j] * $v[$j];
            }
        }

        // Norme
        $norme = sqrt(array_sum(array_map(fn($x) => $x * $x, $v_new)));
        if ($norme == 0) break;

        // Normaliser
        for ($i = 0; $i < $taille; $i++) {
            $v_new[$i] /= $norme;
        }

        // Valeur propre (Rayleigh quotient)
        $valeur_new = 0;
        for ($i = 0; $i < $taille; $i++) {
            $valeur_new += $v[$i] * $v_new[$i];
        }

        if (abs($valeur_new - $valeur) < $eps) {
            $valeur = $valeur_new;
            break;
        }

        $valeur = $valeur_new;
        $v = $v_new;
    }

    return ['valeur' => $valeur, 'vecteur' => $v];
}

// --- K-means (classification non supervisée) ---

function kmeans($data, $k = 3, $max_iter = 100) {
    $n = count($data);
    if ($n < $k) $k = $n;
    if ($n == 0 || $k < 1) return null;
    $p = count($data[0]);

    // Initialisation : choisir k points aléatoirement
    $indices = array_rand($data, $k);
    if (!is_array($indices)) $indices = [$indices];
    $centroids = [];
    foreach ($indices as $idx) {
        $centroids[] = $data[$idx];
    }

    $affectations = array_fill(0, $n, -1);

    for ($iter = 0; $iter < $max_iter; $iter++) {
        $changements = false;

        // Affecter chaque point au centroïde le plus proche
        for ($i = 0; $i < $n; $i++) {
            $min_dist = PHP_FLOAT_MAX;
            $best_cluster = 0;
            for ($c = 0; $c < $k; $c++) {
                $dist = distanceEuclidienne($data[$i], $centroids[$c]);
                if ($dist < $min_dist) {
                    $min_dist = $dist;
                    $best_cluster = $c;
                }
            }
            if ($affectations[$i] != $best_cluster) {
                $affectations[$i] = $best_cluster;
                $changements = true;
            }
        }

        // Recalculer les centroïdes
        for ($c = 0; $c < $k; $c++) {
            $membres = [];
            for ($i = 0; $i < $n; $i++) {
                if ($affectations[$i] == $c) $membres[] = $data[$i];
            }
            if (count($membres) > 0) {
                for ($j = 0; $j < $p; $j++) {
                    $col = array_column($membres, $j);
                    $centroids[$c][$j] = moyenne($col);
                }
            }
        }

        if (!$changements) break;
    }

    // Calcul de l'inertie intra-classe
    $inertie = 0;
    for ($i = 0; $i < $n; $i++) {
        $inertie += distanceEuclidienne($data[$i], $centroids[$affectations[$i]]) ** 2;
    }

    // Taille des clusters
    $tailles = array_fill(0, $k, 0);
    foreach ($affectations as $a) $tailles[$a]++;

    return [
        'affectations' => $affectations,
        'centroids' => $centroids,
        'inertie' => $inertie,
        'tailles' => $tailles,
        'k' => $k,
        'nb_iter' => $iter + 1,
    ];
}

function distanceEuclidienne($a, $b) {
    $sum = 0;
    for ($i = 0; $i < count($a); $i++) {
        $sum += ($a[$i] - $b[$i]) ** 2;
    }
    return sqrt($sum);
}

// --- Classification Ascendante Hiérarchique (CAH) simplifiée ---

function cah($data, $nb_clusters = 3) {
    $n = count($data);
    if ($n < 2) return null;

    // Matrice des distances
    $distances = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $distances[$i][$j] = distanceEuclidienne($data[$i], $data[$j]);
        }
    }

    // Clusters initiaux : chaque point est un cluster
    $clusters = [];
    for ($i = 0; $i < $n; $i++) {
        $clusters[$i] = [$i];
    }

    $historique = [];
    $hauteurs = [];
    $cluster_id = $n;

    while (count($clusters) > 1) {
        $min_dist = PHP_FLOAT_MAX;
        $best_i = -1;
        $best_j = -1;
        $keys = array_keys($clusters);

        for ($a = 0; $a < count($keys); $a++) {
            for ($b = $a + 1; $b < count($keys); $b++) {
                $ci = $keys[$a];
                $cj = $keys[$b];
                // Distance de Ward (lien simple)
                $dist = 0;
                $count = 0;
                foreach ($clusters[$ci] as $pi) {
                    foreach ($clusters[$cj] as $pj) {
                        $d = $pi < $pj ? ($distances[$pi][$pj] ?? 0) : ($distances[$pj][$pi] ?? 0);
                        $dist += $d;
                        $count++;
                    }
                }
                $dist = $count > 0 ? $dist / $count : 0;

                if ($dist < $min_dist) {
                    $min_dist = $dist;
                    $best_i = $ci;
                    $best_j = $cj;
                }
            }
        }

        if ($best_i < 0) break;

        // Fusionner les deux clusters
        $new_cluster = array_merge($clusters[$best_i], $clusters[$best_j]);
        $historique[] = [
            'cluster1' => $best_i,
            'cluster2' => $best_j,
            'hauteur' => $min_dist,
            'nouveau' => $cluster_id,
        ];
        $hauteurs[] = $min_dist;

        unset($clusters[$best_i], $clusters[$best_j]);
        $clusters[$cluster_id] = $new_cluster;
        $cluster_id++;
    }

    // Couper l'arbre pour obtenir nb_clusters
    $clusters_finaux = array_values($clusters);
    $affectations = array_fill(0, $n, 0);
    foreach ($clusters_finaux as $idx => $membres) {
        foreach ($membres as $m) {
            $affectations[$m] = $idx;
        }
    }

    return [
        'affectations' => $affectations,
        'historique' => $historique,
        'hauteurs' => $hauteurs,
        'nb_clusters' => count($clusters_finaux),
        'tailles' => array_map('count', $clusters_finaux),
    ];
}

// --- Interprétation textuelle automatique ---

function interpreterTriAPlat($data, $total, $libelle_question) {
    $max = $data[0];
    $min = $data[0];
    foreach ($data as $item) {
        if ($item['pourcentage'] > $max['pourcentage']) $max = $item;
        if ($item['pourcentage'] < $min['pourcentage']) $min = $item;
    }
    $texte = "Sur un total de {$total} répondants, la modalité « {$max['valeur']} » est la plus représentée avec " . formatPercent($max['pourcentage']) . " des réponses. ";
    $texte .= "À l'inverse, « {$min['valeur']} » obtient la plus faible proportion à " . formatPercent($min['pourcentage']) . ". ";
    if ($max['pourcentage'] > 50) {
        $texte .= "Une majorité claire se dégage en faveur de « {$max['valeur']} ».";
    } elseif ($max['pourcentage'] > 33) {
        $texte .= "Aucune modalité ne recueille une majorité absolue, ce qui suggère une opinion partagée.";
    } else {
        $texte .= "La distribution des réponses est relativement homogène, indiquant une forte hétérogénéité des opinions.";
    }
    return $texte;
}

function interpreterKhi2($result, $seuil = 0.05) {
    $khi2 = formatNumber($result['khi2'], 3);
    $ddl = $result['ddl'];
    $p = formatNumber($result['p_value'], 4);
    $v = formatNumber($result['v_cramer'], 3);

    $texte = "Le test du Khi² donne une valeur de χ² = {$khi2} avec {$ddl} degré(s) de liberté (p = {$p}). ";
    if ($result['p_value'] < $seuil) {
        $texte .= "La relation entre les deux variables est statistiquement significative (p < {$seuil}). ";
    } else {
        $texte .= "Aucune relation statistiquement significative n'est observée entre les deux variables (p ≥ {$seuil}). ";
    }

    if ($result['v_cramer'] < 0.1) {
        $texte .= "Le V de Cramer ({$v}) indique une association très faible.";
    } elseif ($result['v_cramer'] < 0.3) {
        $texte .= "Le V de Cramer ({$v}) indique une association modérée.";
    } elseif ($result['v_cramer'] < 0.5) {
        $texte .= "Le V de Cramer ({$v}) indique une association forte.";
    } else {
        $texte .= "Le V de Cramer ({$v}) indique une association très forte.";
    }
    return $texte;
}

function interpreterAnova($result, $seuil = 0.05) {
    $F = formatNumber($result['F'], 3);
    $p = formatNumber($result['p_value'], 4);
    $texte = "Le test ANOVA donne F = {$F} (p = {$p}). ";
    if ($result['p_value'] < $seuil) {
        $texte .= "Il existe une différence statistiquement significative entre les moyennes des groupes (p < {$seuil}). ";
        $texte .= "Au moins deux groupes présentent des moyennes significativement différentes.";
    } else {
        $texte .= "Aucune différence statistiquement significative n'est observée entre les groupes (p ≥ {$seuil}). ";
        $texte .= "Les moyennes des groupes peuvent être considérées comme égales.";
    }
    return $texte;
}

function interpreterCorrelation($result, $type = 'Pearson', $seuil = 0.05) {
    $r = formatNumber($result['r'], 3);
    $p = formatNumber($result['p_value'], 4);
    $texte = "Le coefficient de corrélation de {$type} est r = {$r} (p = {$p}). ";

    $abs_r = abs($result['r']);
    if ($abs_r < 0.1) $force = "négligeable";
    elseif ($abs_r < 0.3) $force = "faible";
    elseif ($abs_r < 0.5) $force = "modérée";
    elseif ($abs_r < 0.7) $force = "forte";
    else $force = "très forte";

    $sens = $result['r'] > 0 ? "positive" : "négative";
    $texte .= "Il s'agit d'une corrélation {$sens} {$force}. ";

    if ($result['p_value'] < $seuil) {
        $texte .= "Cette corrélation est statistiquement significative (p < {$seuil}).";
    } else {
        $texte .= "Cette corrélation n'est pas statistiquement significative (p ≥ {$seuil}).";
    }
    return $texte;
}

function interpreterACP($result) {
    $texte = "L'analyse en composantes principales a été réalisée sur {$result['nb_individus']} individus et {$result['nb_variables']} variables. ";
    if (count($result['valeurs_propres']) >= 2) {
        $v1 = (float) $result['variance_expliquee'][0];
        $v2 = (float) $result['variance_expliquee'][1];
        $total = $v1 + $v2;
        $texte .= "La première composante explique " . formatNumber($v1, 1) . "% de la variance, et la deuxième " . formatNumber($v2, 1) . "%. ";
        $texte .= "Ensemble, les deux premières composantes expliquent " . formatNumber($total, 1) . "% de la variance totale. ";
        if ($total > 70) {
            $texte .= "Ce pourcentage élevé indique que ces deux dimensions suffisent à résumer l'essentiel de l'information.";
        } else {
            $texte .= "Ce pourcentage suggère que des dimensions supplémentaires pourraient être nécessaires pour une représentation complète.";
        }
    }
    return $texte;
}

function interpreterKmeans($result) {
    $texte = "La classification K-means avec k={$result['k']} a convergé en {$result['nb_iter']} itérations. ";
    $texte .= "L'inertie intra-classe est de " . formatNumber($result['inertie'], 2) . ". ";
    $tailles_str = implode(', ', array_map(fn($i, $t) => "Cluster " . ($i + 1) . " : {$t}", array_keys($result['tailles']), $result['tailles']));
    $texte .= "Répartition : {$tailles_str}.";
    return $texte;
}
