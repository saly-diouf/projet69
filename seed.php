<?php
// Script d'insertion de données de démonstration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/stats.php';

$db = getDB();

$count = $db->query("SELECT COUNT(*) FROM etudes")->fetchColumn();
if ($count > 0) {
    echo "<h2 style='font-family:Inter,sans-serif;color:#f59e0b;'>Des données existent déjà</h2>";
    echo "<p style='font-family:Inter,sans-serif;'><a href='index.php'>→ Accéder au tableau de bord</a></p>";
    exit;
}

// 1. Créer l'étude
$stmt = $db->prepare("INSERT INTO etudes (titre, description, domaine, statut, taille_cible, marge_erreur, niveau_confiance, methode_echantillonnage, date_ouverture) VALUES (?, ?, ?, 'active', 100, 5.00, 95.00, 'aleatoire_simple', CURDATE())");
$stmt->execute([
    "Satisfaction client - Produits électroménagers",
    "Étude visant à mesurer le niveau de satisfaction des clients concernant les produits électroménagers, identifier les axes d'amélioration et segmenter la clientèle.",
    "Marketing — Études de marché"
]);
$etude_id = $db->lastInsertId();

// 2. Créer les sections
$sections_data = [
    ["Informations démographiques", "Questions sur le profil du répondant", 0],
    ["Habitudes de consommation", "Questions sur les habitudes d'achat", 1],
    ["Satisfaction et perception", "Mesure de la satisfaction globale", 2],
];
$section_ids = [];
foreach ($sections_data as $sd) {
    $stmt = $db->prepare("INSERT INTO sections (etude_id, titre, description, ordre) VALUES (?, ?, ?, ?)");
    $stmt->execute([$etude_id, $sd[0], $sd[1], $sd[2]]);
    $section_ids[] = $db->lastInsertId();
}
list($section1_id, $section2_id, $section3_id) = $section_ids;

// Helper pour créer une question
function createQuestion($db, $section_id, $etude_id, $libelle, $type, $obligatoire, $ordre, $echelle_min = 1, $echelle_max = 5, $echelle_libelle_min = null, $echelle_libelle_max = null) {
    $stmt = $db->prepare("INSERT INTO questions (section_id, etude_id, libelle, type, obligatoire, ordre, echelle_min, echelle_max, echelle_libelle_min, echelle_libelle_max) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$section_id, $etude_id, $libelle, $type, $obligatoire, $ordre, $echelle_min, $echelle_max, $echelle_libelle_min, $echelle_libelle_max]);
    return $db->lastInsertId();
}

function addOptions($db, $question_id, $options) {
    foreach ($options as $i => $opt) {
        $stmt = $db->prepare("INSERT INTO reponses_possibles (question_id, libelle, valeur, ordre) VALUES (?, ?, ?, ?)");
        $stmt->execute([$question_id, $opt, $i, $i]);
    }
}

function getOptionIds($db, $question_id) {
    $stmt = $db->prepare("SELECT id, libelle FROM reponses_possibles WHERE question_id = ? ORDER BY ordre");
    $stmt->execute([$question_id]);
    $opts = [];
    foreach ($stmt->fetchAll() as $row) {
        $opts[$row['libelle']] = $row['id'];
    }
    return $opts;
}

// Section 1 : Démographique
$q1 = createQuestion($db, $section1_id, $etude_id, "Quel est votre genre ?", "fermee_une", 1, 0);
addOptions($db, $q1, ["Homme", "Femme"]);

$q2 = createQuestion($db, $section1_id, $etude_id, "Quelle est votre tranche d'âge ?", "fermee_une", 1, 1);
addOptions($db, $q2, ["18-25", "26-35", "36-45", "46-55", "56+"]);

$q3 = createQuestion($db, $section1_id, $etude_id, "Quelle est votre profession ?", "fermee_une", 1, 2);
addOptions($db, $q3, ["Employé", "Cadre", "Indépendant", "Étudiant", "Retraité", "Sans activité"]);

// Section 2 : Habitudes
$q4 = createQuestion($db, $section2_id, $etude_id, "À quelle fréquence achetez-vous des produits électroménagers ?", "fermee_une", 1, 0);
addOptions($db, $q4, ["Moins d'une fois par an", "1-2 fois par an", "3-5 fois par an", "Plus de 5 fois par an"]);

$q5 = createQuestion($db, $section2_id, $etude_id, "Quels canaux d'achat utilisez-vous ?", "fermee_multiple", 1, 1);
addOptions($db, $q5, ["Magasin physique", "Site web", "Application mobile", "Marché", "Autre"]);

$q6 = createQuestion($db, $section2_id, $etude_id, "Quel est votre budget annuel pour l'électroménager (en FCFA) ?", "numerique", 1, 2);

// Section 3 : Satisfaction
$q7 = createQuestion($db, $section3_id, $etude_id, "Quel est votre niveau de satisfaction global concernant nos produits ?", "echelle", 1, 0, 1, 10, "Pas du tout satisfait", "Très satisfait");

$q8 = createQuestion($db, $section3_id, $etude_id, "Recommanderiez-vous nos produits à votre entourage ?", "likert", 1, 1);
$q9 = createQuestion($db, $section3_id, $etude_id, "Évaluez la qualité de nos produits", "likert", 1, 2);
$q10 = createQuestion($db, $section3_id, $etude_id, "Évaluez le rapport qualité-prix", "likert", 1, 3);
$q11 = createQuestion($db, $section3_id, $etude_id, "Évaluez le service client", "likert", 1, 4);

$likert_opts = ["Pas du tout d'accord", "Pas d'accord", "Neutre", "D'accord", "Tout à fait d'accord"];
foreach ([$q8, $q9, $q10, $q11] as $qid) {
    foreach ($likert_opts as $i => $opt) {
        $stmt = $db->prepare("INSERT INTO reponses_possibles (question_id, libelle, valeur, ordre) VALUES (?, ?, ?, ?)");
        $stmt->execute([$qid, $opt, $i + 1, $i]);
    }
}

$q12 = createQuestion($db, $section3_id, $etude_id, "Quelles améliorations suggérez-vous ?", "ouverte", 0, 5);

// 4. Générer des réponses simulées (30 répondants)
$q1_opts = getOptionIds($db, $q1);
$q2_opts = getOptionIds($db, $q2);
$q3_opts = getOptionIds($db, $q3);
$q4_opts = getOptionIds($db, $q4);
$q5_opts = getOptionIds($db, $q5);
$q8_opts = getOptionIds($db, $q8);
$q9_opts = getOptionIds($db, $q9);
$q10_opts = getOptionIds($db, $q10);
$q11_opts = getOptionIds($db, $q11);

$genres = ["Homme", "Femme"];
$ages = ["18-25", "26-35", "36-45", "46-55", "56+"];
$professions = ["Employé", "Cadre", "Indépendant", "Étudiant", "Retraité", "Sans activité"];
$frequences = ["Moins d'une fois par an", "1-2 fois par an", "3-5 fois par an", "Plus de 5 fois par an"];
$suggestions = [
    "Améliorer la durabilité des produits",
    "Plus de choix de couleurs",
    "Réduire les prix",
    "Meilleur service après-vente",
    "Garantie plus longue",
    "Plus de points de vente",
    "Application mobile plus intuitive",
];
$likert_keys = array_keys($likert_opts);

for ($i = 0; $i < 30; $i++) {
    $token = generateToken();
    $genre = $genres[array_rand($genres)];
    $age_val = $ages[array_rand($ages)];
    $age_num = rand(18, 65);
    $profession = $professions[array_rand($professions)];

    $stmt = $db->prepare("INSERT INTO respondents (etude_id, token, statut, date_debut, date_fin, age, genre, ville, profession) VALUES (?, ?, 'termine', NOW(), NOW(), ?, ?, 'Dakar', ?)");
    $stmt->execute([$etude_id, $token, $age_num, $genre == "Homme" ? "M" : "F", $profession]);
    $rid = $db->lastInsertId();

    // Q1
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, reponse_possibles_id) VALUES (?, ?, ?)");
    $stmt->execute([$rid, $q1, $q1_opts[$genre]]);

    // Q2
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, reponse_possibles_id) VALUES (?, ?, ?)");
    $stmt->execute([$rid, $q2, $q2_opts[$age_val]]);

    // Q3
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, reponse_possibles_id) VALUES (?, ?, ?)");
    $stmt->execute([$rid, $q3, $q3_opts[$profession]]);

    // Q4
    $freq = $frequences[array_rand($frequences)];
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, reponse_possibles_id) VALUES (?, ?, ?)");
    $stmt->execute([$rid, $q4, $q4_opts[$freq]]);

    // Q5 (multiple)
    $nb_canaux = rand(1, 3);
    $canaux_keys = array_rand($q5_opts, $nb_canaux);
    if (!is_array($canaux_keys)) $canaux_keys = [$canaux_keys];
    $selected = [];
    foreach ($canaux_keys as $key) {
        $selected[] = $q5_opts[$key];
    }
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, valeur_multiple) VALUES (?, ?, ?)");
    $stmt->execute([$rid, $q5, json_encode($selected)]);

    // Q6 : budget
    $budget = rand(50000, 500000);
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, valeur_numerique) VALUES (?, ?, ?)");
    $stmt->execute([$rid, $q6, $budget]);

    // Q7 : satisfaction (1-10)
    $satisfaction = rand(3, 10);
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, valeur_numerique) VALUES (?, ?, ?)");
    $stmt->execute([$rid, $q7, $satisfaction]);

    // Q8-Q11 : Likert
    foreach ([$q8 => $q8_opts, $q9 => $q9_opts, $q10 => $q10_opts, $q11 => $q11_opts] as $qid => $opts) {
        $likert_idx = array_rand($likert_keys);
        $opt_libelle = $likert_opts[$likert_keys[$likert_idx]];
        $opt_id = $opts[$opt_libelle];
        $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, reponse_possibles_id) VALUES (?, ?, ?)");
        $stmt->execute([$rid, $qid, $opt_id]);
    }

    // Q12 : suggestion (50%)
    if (rand(0, 1)) {
        $suggestion = $suggestions[array_rand($suggestions)];
        $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, valeur_texte) VALUES (?, ?, ?)");
        $stmt->execute([$rid, $q12, $suggestion]);
    }
}

echo "<h2 style='font-family:Inter,sans-serif;color:#10b981;'>✓ Données de démonstration insérées avec succès !</h2>";
echo "<p style='font-family:Inter,sans-serif;'>Une étude exemple avec 12 questions et 30 répondants a été créée.</p>";
echo "<p style='font-family:Inter,sans-serif;'><a href='index.php' style='color:#4f46e5;font-weight:600;'>→ Accéder au tableau de bord</a></p>";
