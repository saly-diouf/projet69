<?php
require_once __DIR__ . '/config/config.php';

$db = getDB();

// Check if already seeded
$count = $db->query("SELECT COUNT(*) FROM sections")->fetchColumn();
if ($count > 0) {
    die("Les données ont déjà été insérées. Supprimez-les d'abord si vous voulez réinitialiser.\n");
}

$etudes = $db->query("SELECT id, titre FROM etudes ORDER BY id")->fetchAll();

// ============================================================
// Helper: insert section
// ============================================================
function addSection($db, $etude_id, $titre, $description, $ordre) {
    $stmt = $db->prepare("INSERT INTO sections (etude_id, titre, description, ordre) VALUES (?, ?, ?, ?)");
    $stmt->execute([$etude_id, $titre, $description, $ordre]);
    return $db->lastInsertId();
}

// ============================================================
// Helper: insert question
// ============================================================
function addQuestion($db, $section_id, $etude_id, $libelle, $type, $ordre, $obligatoire = 1, $echelle_min = null, $echelle_max = null, $echelle_libelle_min = null, $echelle_libelle_max = null) {
    $stmt = $db->prepare("INSERT INTO questions (section_id, etude_id, libelle, type, obligatoire, ordre, echelle_min, echelle_max, echelle_libelle_min, echelle_libelle_max) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$section_id, $etude_id, $libelle, $type, $obligatoire, $ordre, $echelle_min, $echelle_max, $echelle_libelle_min, $echelle_libelle_max]);
    return $db->lastInsertId();
}

// ============================================================
// Helper: insert reponse possible
// ============================================================
function addReponsePossible($db, $question_id, $libelle, $valeur, $ordre) {
    $stmt = $db->prepare("INSERT INTO reponses_possibles (question_id, libelle, valeur, ordre) VALUES (?, ?, ?, ?)");
    $stmt->execute([$question_id, $libelle, $valeur, $ordre]);
    return $db->lastInsertId();
}

// ============================================================
// Helper: insert respondent
// ============================================================
function addRespondent($db, $etude_id, $age, $genre, $ville, $profession, $statut = 'termine') {
    $token = bin2hex(random_bytes(16));
    $stmt = $db->prepare("INSERT INTO respondents (etude_id, token, statut, date_invitation, date_debut, date_fin, age, genre, ville, profession, ip_address, user_agent) VALUES (?, ?, ?, NOW(), NOW(), NOW(), ?, ?, ?, ?, '127.0.0.1', 'Mozilla/5.0')");
    $stmt->execute([$etude_id, $token, $statut, $age, $genre, $ville, $profession]);
    return $db->lastInsertId();
}

// ============================================================
// Helper: insert reponse
// ============================================================
function addReponse($db, $respondent_id, $question_id, $reponse_possibles_id = null, $valeur_texte = null, $valeur_numerique = null, $valeur_multiple = null) {
    $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, reponse_possibles_id, valeur_texte, valeur_numerique, valeur_multiple) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$respondent_id, $question_id, $reponse_possibles_id, $valeur_texte, $valeur_numerique, $valeur_multiple]);
}

// ============================================================
// Likert scale helper
// ============================================================
function addLikertChoices($db, $question_id) {
    $choices = [
        ['Très insatisfait', 1, 0],
        ['Insatisfait', 2, 1],
        ['Neutre', 3, 2],
        ['Satisfait', 4, 3],
        ['Très satisfait', 5, 4],
    ];
    $ids = [];
    foreach ($choices as $c) {
        $ids[] = addReponsePossible($db, $question_id, $c[0], $c[1], $c[2]);
    }
    return $ids;
}

// Random Likert response weighted towards positive
function randomLikert($likert_ids) {
    $weights = [1, 2, 5, 12, 8]; // weighted towards satisfied
    $total = array_sum($weights);
    $r = mt_rand(1, $total);
    $cum = 0;
    for ($i = 0; $i < count($weights); $i++) {
        $cum += $weights[$i];
        if ($r <= $cum) return $likert_ids[$i];
    }
    return $likert_ids[2];
}

// ============================================================
// DATA DEFINITIONS FOR EACH STUDY
// ============================================================

$studies_data = [

    // ===== ETUDE 2: Satisfaction client - Supermarchés =====
    2 => [
        'sections' => [
            [
                'titre' => 'Profil du client',
                'description' => 'Informations démographiques',
                'questions' => [
                    ['libelle' => 'Quelle est votre tranche d\'âge ?', 'type' => 'fermee_une', 'choices' => ['18-24 ans', '25-34 ans', '35-44 ans', '45-54 ans', '55-64 ans', '65 ans et +']],
                    ['libelle' => 'Quel est votre genre ?', 'type' => 'fermee_une', 'choices' => ['Homme', 'Femme', 'Autre']],
                    ['libelle' => 'Dans quelle ville résidez-vous ?', 'type' => 'ouverte'],
                    ['libelle' => 'Quelle est votre profession ?', 'type' => 'ouverte'],
                ],
            ],
            [
                'titre' => 'Habitudes d\'achat',
                'description' => 'Fréquence et modes d\'achat',
                'questions' => [
                    ['libelle' => 'À quelle fréquence allez-vous au supermarché ?', 'type' => 'fermee_une', 'choices' => ['Plusieurs fois par semaine', 'Une fois par semaine', '2-3 fois par mois', 'Une fois par mois', 'Moins souvent']],
                    ['libelle' => 'Quels rayons fréquentez-vous le plus ?', 'type' => 'fermee_multiple', 'choices' => ['Fruits & Légumes', 'Viande & Poisson', 'Produits laitiers', 'Surgelés', 'Boissons', 'Hygiène & Beauté', 'Boulangerie']],
                    ['libelle' => 'Quel est votre budget mensuel moyen pour les courses ?', 'type' => 'numerique'],
                    ['libelle' => 'Faites-vous vos courses en ligne ?', 'type' => 'fermee_une', 'choices' => ['Oui, régulièrement', 'Oui, occasionnellement', 'Non, jamais']],
                ],
            ],
            [
                'titre' => 'Satisfaction globale',
                'description' => 'Évaluation de l\'expérience',
                'questions' => [
                    ['libelle' => 'Satisfaction globale concernant la qualité des produits', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant les prix', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant le service client', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant la propreté du magasin', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant la disponibilité des produits', 'type' => 'likert'],
                    ['libelle' => 'Sur une échelle de 1 à 10, comment notez-vous votre expérience globale ?', 'type' => 'echelle', 'echelle_min' => 1, 'echelle_max' => 10, 'echelle_libelle_min' => 'Très mauvais', 'echelle_libelle_max' => 'Excellent'],
                    ['libelle' => 'Quelles améliorations aimeriez-vous voir ?', 'type' => 'ouverte'],
                    ['libelle' => 'Recommanderiez-vous ce supermarché à un ami ?', 'type' => 'fermee_une', 'choices' => ['Oui, certainement', 'Oui, probablement', 'Je ne sais pas', 'Non, probablement pas', 'Non, certainement pas']],
                ],
            ],
        ],
        'respondents' => 45,
    ],

    // ===== ETUDE 3: Préférences automobiles 2026 =====
    3 => [
        'sections' => [
            [
                'titre' => 'Profil et véhicule actuel',
                'description' => 'Votre situation actuelle',
                'questions' => [
                    ['libelle' => 'Quelle est votre tranche d\'âge ?', 'type' => 'fermee_une', 'choices' => ['18-24 ans', '25-34 ans', '35-44 ans', '45-54 ans', '55-64 ans', '65 ans et +']],
                    ['libelle' => 'Quel type de véhicule possédez-vous actuellement ?', 'type' => 'fermee_une', 'choices' => ['Berline', 'SUV / Crossover', 'Citadine', 'Monospace', 'Utilitaire', 'Aucun véhicule']],
                    ['libelle' => 'Depuis combien d\'années avez-vous votre véhicule actuel ?', 'type' => 'numerique'],
                    ['libelle' => 'Quelle est votre ville de résidence ?', 'type' => 'ouverte'],
                ],
            ],
            [
                'titre' => 'Critères de choix',
                'description' => 'Ce qui compte dans le choix d\'un véhicule',
                'questions' => [
                    ['libelle' => 'Quels sont vos critères les plus importants ?', 'type' => 'fermee_multiple', 'choices' => ['Prix', 'Consommation de carburant', 'Design / Style', 'Sécurité', 'Espace / Confort', 'Technologie embarquée', 'Impact environnemental', 'Marque']],
                    ['libelle' => 'Quel type de motorisation préférez-vous ?', 'type' => 'fermee_une', 'choices' => ['Essence', 'Diesel', 'Hybride', 'Électrique', 'Je n\'ai pas de préférence']],
                    ['libelle' => 'Quel est votre budget pour un véhicule ?', 'type' => 'fermee_une', 'choices' => ['Moins de 15 000 €', '15 000 - 25 000 €', '25 000 - 40 000 €', '40 000 - 60 000 €', 'Plus de 60 000 €']],
                    ['libelle' => 'Importance du design du véhicule', 'type' => 'likert'],
                    ['libelle' => 'Importance de la consommation', 'type' => 'likert'],
                    ['libelle' => 'Importance de la sécurité', 'type' => 'likert'],
                ],
            ],
            [
                'titre' => 'Marques et préférences',
                'description' => 'Vos marques préférées',
                'questions' => [
                    ['libelle' => 'Quelles marques vous attirent le plus ?', 'type' => 'fermee_multiple', 'choices' => ['Renault', 'Peugeot', 'Citroën', 'Volkswagen', 'Toyota', 'Tesla', 'BMW', 'Mercedes', 'Audi', 'Hyundai']],
                    ['libelle' => 'Classez ces critères par ordre d\'importance (1 = le plus important)', 'type' => 'classement', 'choices' => ['Prix', 'Consommation', 'Design', 'Sécurité', 'Technologie']],
                    ['libelle' => 'Seriez-vous intéressé par un véhicule électrique dans les 2 prochaines années ?', 'type' => 'fermee_une', 'choices' => ['Oui, certainement', 'Oui, peut-être', 'Je ne sais pas encore', 'Non, probablement pas', 'Non, certainement pas']],
                    ['libelle' => 'Sur une échelle de 1 à 10, quelle est votre satisfaction globale avec votre véhicule actuel ?', 'type' => 'echelle', 'echelle_min' => 1, 'echelle_max' => 10, 'echelle_libelle_min' => 'Très insatisfait', 'echelle_libelle_max' => 'Très satisfait'],
                    ['libelle' => 'Quelles fonctionnalités technologiques aimeriez-vous avoir ?', 'type' => 'ouverte'],
                ],
            ],
        ],
        'respondents' => 38,
    ],

    // ===== ETUDE 4: Habitudes de télétravail =====
    4 => [
        'sections' => [
            [
                'titre' => 'Profil professionnel',
                'description' => 'Votre situation de travail',
                'questions' => [
                    ['libelle' => 'Quelle est votre situation professionnelle ?', 'type' => 'fermee_une', 'choices' => ['Salarié temps plein', 'Salarié temps partiel', 'Indépendant / Freelance', 'Étudiant', 'Sans activité', 'Retraité']],
                    ['libelle' => 'Quel est votre secteur d\'activité ?', 'type' => 'fermee_une', 'choices' => ['Informatique / Tech', 'Finance', 'Marketing / Communication', 'Ressources Humaines', 'Commerce', 'Santé', 'Éducation', 'Autre']],
                    ['libelle' => 'Depuis combien d\'années travaillez-vous ?', 'type' => 'numerique'],
                    ['libelle' => 'Quelle est votre ville de résidence ?', 'type' => 'ouverte'],
                ],
            ],
            [
                'titre' => 'Pratiques de télétravail',
                'description' => 'Votre expérience du travail à distance',
                'questions' => [
                    ['libelle' => 'Combien de jours par semaine télétravaillez-vous en moyenne ?', 'type' => 'numerique'],
                    ['libelle' => 'Quels outils utilisez-vous pour le télétravail ?', 'type' => 'fermee_multiple', 'choices' => ['Zoom', 'Microsoft Teams', 'Slack', 'Google Meet', 'Skype', 'Discord', 'Trello', 'Notion']],
                    ['libelle' => 'Satisfaction concernant votre équipement à domicile', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant la connexion internet', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant l\'équilibre vie pro / vie perso', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant la communication avec l\'équipe', 'type' => 'likert'],
                ],
            ],
            [
                'titre' => 'Préférences et perspectives',
                'description' => 'Vos souhaits pour l\'avenir',
                'questions' => [
                    ['libelle' => 'Préférez-vous le télétravail ou le présentiel ?', 'type' => 'fermee_une', 'choices' => ['100% télétravail', 'Télétravail majoritaire', 'Hybride (50/50)', 'Présentiel majoritaire', '100% présentiel']],
                    ['libelle' => 'Quels sont les avantages du télétravail pour vous ?', 'type' => 'fermee_multiple', 'choices' => ['Gain de temps de transport', 'Flexibilité des horaires', 'Confort du domicile', 'Concentration accrue', 'Économies financières', 'Plus de temps pour la famille']],
                    ['libelle' => 'Quels sont les inconvénients du télétravail ?', 'type' => 'fermee_multiple', 'choices' => ['Isolement social', 'Difficulté à se déconnecter', 'Manque d\'équipement', 'Distractions domestiques', 'Moins de visibilité managériale', 'Problèmes techniques']],
                    ['libelle' => 'Sur une échelle de 1 à 10, quelle est votre productivité en télétravail vs au bureau ?', 'type' => 'echelle', 'echelle_min' => 1, 'echelle_max' => 10, 'echelle_libelle_min' => 'Beaucoup moins productif', 'echelle_libelle_max' => 'Beaucoup plus productif'],
                    ['libelle' => 'Quelles améliorations aimeriez-vous pour votre setup de télétravail ?', 'type' => 'ouverte'],
                ],
            ],
        ],
        'respondents' => 30,
    ],

    // ===== ETUDE 5: Satisfaction bancaire en ligne =====
    5 => [
        'sections' => [
            [
                'titre' => 'Profil bancaire',
                'description' => 'Votre relation avec votre banque',
                'questions' => [
                    ['libelle' => 'Quelle est votre tranche d\'âge ?', 'type' => 'fermee_une', 'choices' => ['18-24 ans', '25-34 ans', '35-44 ans', '45-54 ans', '55-64 ans', '65 ans et +']],
                    ['libelle' => 'Quelle est votre banque principale ?', 'type' => 'fermee_une', 'choices' => ['Crédit Agricole', 'BNP Paribas', 'Société Générale', 'LCL', 'Banque Populaire', 'Caisse d\'Épargne', 'La Banque Postale', 'Banque en ligne (Boursorama, ING, etc.)']],
                    ['libelle' => 'Depuis combien d\'années êtes-vous client de cette banque ?', 'type' => 'numerique'],
                    ['libelle' => 'Quelle est votre ville de résidence ?', 'type' => 'ouverte'],
                ],
            ],
            [
                'titre' => 'Utilisation des services en ligne',
                'description' => 'Vos habitudes numériques',
                'questions' => [
                    ['libelle' => 'À quelle fréquence utilisez-vous votre espace bancaire en ligne ?', 'type' => 'fermee_une', 'choices' => ['Quotidiennement', 'Plusieurs fois par semaine', 'Une fois par semaine', 'Quelques fois par mois', 'Rarement']],
                    ['libelle' => 'Quelles fonctionnalités utilisez-vous le plus ?', 'type' => 'fermee_multiple', 'choices' => ['Consultation des comptes', 'Virements', 'Gestion des cartes', 'Catégorisation des dépenses', 'Messagerie avec le conseiller', 'Souscription de produits', 'Budget / Épargne']],
                    ['libelle' => 'Utilisez-vous l\'application mobile de votre banque ?', 'type' => 'fermee_une', 'choices' => ['Oui, principalement', 'Oui, parfois', 'Non, je préfère le site web', 'Non, je n\'utilise pas les services en ligne']],
                    ['libelle' => 'Satisfaction concernant la facilité d\'utilisation du site web', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant l\'application mobile', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant la sécurité des services', 'type' => 'likert'],
                ],
            ],
            [
                'titre' => 'Satisfaction et recommandation',
                'description' => 'Votre opinion globale',
                'questions' => [
                    ['libelle' => 'Satisfaction concernant les frais bancaires', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant le service client en ligne', 'type' => 'likert'],
                    ['libelle' => 'Satisfaction concernant la rapidité des transactions', 'type' => 'likert'],
                    ['libelle' => 'Sur une échelle de 1 à 10, comment notez-vous votre banque en ligne ?', 'type' => 'echelle', 'echelle_min' => 1, 'echelle_max' => 10, 'echelle_libelle_min' => 'Très mauvais', 'echelle_libelle_max' => 'Excellent'],
                    ['libelle' => 'Recommanderiez-vous votre banque à un proche ?', 'type' => 'fermee_une', 'choices' => ['Oui, certainement', 'Oui, probablement', 'Je ne sais pas', 'Non, probablement pas', 'Non, certainement pas']],
                    ['libelle' => 'Quelles améliorations aimeriez-vous voir dans les services en ligne ?', 'type' => 'ouverte'],
                ],
            ],
        ],
        'respondents' => 35,
    ],
];

// ============================================================
// INSERT DATA
// ============================================================

$villes = ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux', 'Nantes', 'Strasbourg', 'Lille', 'Nice', 'Rennes', 'Montpellier', 'Grenoble'];
$professions = ['Employé', 'Cadre', 'Technicien', 'Commerçant', 'Enseignant', 'Ingénieur', 'Étudiant', 'Retraité', 'Artisan', 'Médecin', 'Informaticien', 'Commercial'];
$genres = ['M', 'F'];

foreach ($etudes as $etude) {
    $etude_id = $etude['id'];
    if (!isset($studies_data[$etude_id])) continue;

    echo "Étude #{$etude_id}: {$etude['titre']}\n";

    $data = $studies_data[$etude_id];
    $questions_map = []; // question_id => ['type' => ..., 'choices' => [...]]

    // Create sections and questions
    foreach ($data['sections'] as $sec_idx => $sec) {
        $section_id = addSection($db, $etude_id, $sec['titre'], $sec['description'], $sec_idx);

        foreach ($sec['questions'] as $q_idx => $q) {
            $q_id = addQuestion(
                $db, $section_id, $etude_id, $q['libelle'], $q['type'], $q_idx, 1,
                $q['echelle_min'] ?? null, $q['echelle_max'] ?? null,
                $q['echelle_libelle_min'] ?? null, $q['echelle_libelle_max'] ?? null
            );

            $choice_ids = [];
            if (isset($q['choices'])) {
                foreach ($q['choices'] as $c_idx => $choice) {
                    $choice_ids[] = addReponsePossible($db, $q_id, $choice, $c_idx + 1, $c_idx);
                }
            } elseif ($q['type'] === 'likert') {
                $choice_ids = addLikertChoices($db, $q_id);
            }

            $questions_map[$q_id] = ['type' => $q['type'], 'choices' => $choice_ids];
        }
    }

    // Create respondents and responses
    $nb_respondents = $data['respondents'];
    for ($r = 0; $r < $nb_respondents; $r++) {
        $age = mt_rand(18, 70);
        $genre = $genres[array_rand($genres)];
        $ville = $villes[array_rand($villes)];
        $profession = $professions[array_rand($professions)];

        $respondent_id = addRespondent($db, $etude_id, $age, $genre, $ville, $profession);

        // Generate responses for each question
        foreach ($questions_map as $q_id => $q_info) {
            $type = $q_info['type'];
            $choices = $q_info['choices'];

            switch ($type) {
                case 'fermee_une':
                    $idx = mt_rand(0, count($choices) - 1);
                    addReponse($db, $respondent_id, $q_id, $choices[$idx]);
                    break;

                case 'fermee_multiple':
                    $nb_pick = mt_rand(1, min(3, count($choices)));
                    $picked = array_rand($choices, $nb_pick);
                    if (!is_array($picked)) $picked = [$picked];
                    $picked_ids = array_map(function($i) use ($choices) { return $choices[$i]; }, $picked);
                    addReponse($db, $respondent_id, $q_id, null, null, null, json_encode($picked_ids));
                    break;

                case 'likert':
                    $rp_id = randomLikert($choices);
                    addReponse($db, $respondent_id, $q_id, $rp_id);
                    break;

                case 'echelle':
                    $val = mt_rand($q_info['echelle_min'] ?? 1, $q_info['echelle_max'] ?? 10);
                    addReponse($db, $respondent_id, $q_id, null, null, $val);
                    break;

                case 'numerique':
                    $val = mt_rand(1, 50);
                    addReponse($db, $respondent_id, $q_id, null, null, $val);
                    break;

                case 'ouverte':
                    $texts = [
                        'Paris' => 'Très bonne expérience globalement',
                        'Lyon' => 'Service de qualité, quelques améliorations possibles',
                        'Marseille' => 'Satisfait dans l\'ensemble',
                        'Toulouse' => 'Bon rapport qualité-prix',
                        'Bordeaux' => 'Je recommande, très professionnel',
                        'Nantes' => 'Experience positive, équipe à l\'écoute',
                        'Strasbourg' => 'Rien à redire, tout est parfait',
                        'Lille' => 'Quelques soucis mais vite résolus',
                        'Nice' => 'Très bien, je suis fidèle client',
                        'Rennes' => 'Améliorer les délais de réponse',
                        'Montpellier' => 'Bon service mais prix un peu élevés',
                        'Grenoble' => 'Excellente qualité, je suis très satisfait',
                    ];
                    $txt = $texts[array_rand($texts)];
                    addReponse($db, $respondent_id, $q_id, null, $txt);
                    break;

                case 'classement':
                    $order = range(0, count($choices) - 1);
                    shuffle($order);
                    addReponse($db, $respondent_id, $q_id, null, null, null, json_encode($order));
                    break;
            }
        }
    }

    // Update study status to active for studies that have responses
    $db->prepare("UPDATE etudes SET statut = 'active' WHERE id = ? AND statut = 'brouillon'")->execute([$etude_id]);

    echo "  → " . count($data['sections']) . " sections, " . count($questions_map) . " questions, {$nb_respondents} répondants\n";
}

echo "\nTerminé ! Données insérées avec succès.\n";

// Stats
$total_sections = $db->query("SELECT COUNT(*) FROM sections")->fetchColumn();
$total_questions = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$total_choices = $db->query("SELECT COUNT(*) FROM reponses_possibles")->fetchColumn();
$total_respondents = $db->query("SELECT COUNT(*) FROM respondents")->fetchColumn();
$total_reponses = $db->query("SELECT COUNT(*) FROM reponses")->fetchColumn();

echo "\nRésumé:\n";
echo "  Sections: $total_sections\n";
echo "  Questions: $total_questions\n";
echo "  Réponses possibles: $total_choices\n";
echo "  Répondants: $total_respondents\n";
echo "  Réponses: $total_reponses\n";
