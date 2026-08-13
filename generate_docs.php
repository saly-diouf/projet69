<?php
require_once __DIR__ . '/config/config.php';

if (!isLoggedIn()) {
    redirect(APP_URL . '/auth/login.php');
}

$docs_dir = __DIR__ . '/docs';
if (!is_dir($docs_dir)) {
    mkdir($docs_dir, 0777, true);
}

// ===== Word XML Helpers =====

function fr_accents($text) {
    static $tr = null;
    if ($tr === null) {
        $tr = [
            'echantillonnage'=>'échantillonnage','echantillon'=>'échantillon',
            'etudiants'=>'étudiants','etudiant'=>'étudiant','etudes'=>'études','etude'=>'étude',
            'ecrans'=>'écrans','ecran'=>'écran','creation'=>'création','creer'=>'créer','cree'=>'crée','creee'=>'créée',
            'presentation'=>'présentation','presenter'=>'présenter',
            'generation'=>'génération','generer'=>'générer','genere'=>'génère','generees'=>'générées',
            'repondants'=>'répondants','repondant'=>'répondant','reponses'=>'réponses','reponse'=>'réponse','repondre'=>'répondre',
            'recoit'=>'reçoit','specialisee'=>'spécialisée','specialise'=>'spécialisé',
            'realisee'=>'réalisée','realise'=>'réalisé','realisation'=>'réalisation',
            'reference'=>'référence','references'=>'références','experience'=>'expérience','experiences'=>'expériences',
            'periode'=>'période','periodes'=>'périodes','categorie'=>'catégorie','categories'=>'catégories',
            'modele'=>'modèle','modeles'=>'modèles','donnees'=>'données','donnee'=>'donnée',
            'methode'=>'méthode','methodes'=>'méthodes','numero'=>'numéro',
            'preference'=>'préférence','preferences'=>'préférences','precise'=>'précise','precis'=>'précis',
            'premiere'=>'première','premieres'=>'premières','derniere'=>'dernière','dernieres'=>'dernières',
            'complete'=>'complète','completer'=>'compléter','completee'=>'complétée',
            'interet'=>'intérêt','etre'=>'être','meme'=>'même','memes'=>'mêmes',
            'theme'=>'thème','themes'=>'thèmes','theorie'=>'théorie','theorique'=>'théorique',
            'scenario'=>'scénario','scenarios'=>'scénarios','resume'=>'résumé',
            'securite'=>'sécurité','qualite'=>'qualité','probabilite'=>'probabilité','probabilites'=>'probabilités',
            'echelle'=>'échelle','echelles'=>'échelles','element'=>'élément','elements'=>'éléments',
            'fenetre'=>'fenêtre','fenetres'=>'fenêtres','apres'=>'après','tres'=>'très',
            'acces'=>'accès','succes'=>'succès','progres'=>'progrès',
            'telecharger'=>'télécharger','telechargement'=>'téléchargement','telephone'=>'téléphone',
            'interpretation'=>'interprétation','interpreter'=>'interpréter',
            'differents'=>'différents','different'=>'différent','differente'=>'différente','differentes'=>'différentes',
            'difference'=>'différence','differences'=>'différences',
            'avancee'=>'avancée','avancees'=>'avancées','utilisee'=>'utilisée','utilise'=>'utilisé','utilises'=>'utilisés',
            'fermee'=>'fermée','fermees'=>'fermées','deployee'=>'déployée','deploiement'=>'déploiement',
            'decrite'=>'décrite','decrire'=>'décrire','decrit'=>'décrit','decrivant'=>'décrivant',
            'decision'=>'décision','decisions'=>'décisions','decouvrir'=>'découvrir','dediee'=>'dédiée',
            'defaut'=>'défaut','defauts'=>'défauts','definir'=>'définir','deja'=>'déjà',
            'dependance'=>'dépendance','dependant'=>'dépendant','deroule'=>'déroule','deroulement'=>'déroulement',
            'detail'=>'détail','details'=>'détails','detaille'=>'détaillé','detaillee'=>'détaillée','detailles'=>'détaillés',
            'detecter'=>'détecter','determination'=>'détermination','determiner'=>'déterminer',
            'disponibilite'=>'disponibilité','eboulis'=>'éboulis','empeche'=>'empêche',
            'enregistre'=>'enregistré','entree'=>'entrée','entrees'=>'entrées','estimee'=>'estimée',
            'etape'=>'étape','etapes'=>'étapes','etat'=>'état','etats'=>'états',
            'etait'=>'était','etaient'=>'étaient','etant'=>'étant','eviter'=>'éviter','evite'=>'évite',
            'exterieur'=>'extérieur','facade'=>'façade',
            'fonctionnalite'=>'fonctionnalité','fonctionnalites'=>'fonctionnalités',
            'hierarchique'=>'hiérarchique','integre'=>'intégré','integree'=>'intégrée','integres'=>'intégrés',
            'integration'=>'intégration','intitule'=>'intitulé','journaliere'=>'journalière',
            'lateral'=>'latéral','laterale'=>'latérale','legende'=>'légende','legendes'=>'légendes',
            'libelle'=>'libellé','libelles'=>'libellés','limitee'=>'limitée',
            'marche'=>'marché','marches'=>'marchés','matiere'=>'matière','matieres'=>'matières',
            'modalite'=>'modalité','modalites'=>'modalités',
            'necessaire'=>'nécessaire','necessite'=>'nécessite','necessiter'=>'nécessiter',
            'numerique'=>'numérique','numeriques'=>'numériques',
            'operation'=>'opération','operations'=>'opérations','operationnel'=>'opérationnel',
            'parametre'=>'paramètre','parametres'=>'paramètres','parametrage'=>'paramétrage',
            'particuliere'=>'particulière','particulierement'=>'particulièrement',
            'passe'=>'passé','preparation'=>'préparation','preparer'=>'préparer',
            'preselectionne'=>'présélectionné','previsualisation'=>'prévisualisation','previsualiser'=>'prévisualiser',
            'procedural'=>'procédural','procedure'=>'procédure','procedures'=>'procédures',
            'recolte'=>'récolte','redaction'=>'rédaction','rediger'=>'rédiger',
            'reduire'=>'réduire','reduit'=>'réduit','reduite'=>'réduite',
            'regle'=>'règle','regles'=>'règles','repartition'=>'répartition',
            'representer'=>'représenter','represente'=>'représente','representent'=>'représentent',
            'reseaux'=>'réseaux','resolution'=>'résolution','resultat'=>'résultat','resultats'=>'résultats',
            'reunion'=>'réunion','reunions'=>'réunions','reussite'=>'réussite',
            'reinitialiser'=>'réinitialiser','reserve'=>'réservé',
            'reapparait'=>'réapparaît','reapparaissent'=>'réapparaissent',
            'role'=>'rôle','roles'=>'rôles','schema'=>'schéma','schemas'=>'schémas',
            'selection'=>'sélection','selectionner'=>'sélectionner','selectionne'=>'sélectionné','selectionnee'=>'sélectionnée',
            'separateur'=>'séparateur','separation'=>'séparation','sequence'=>'séquence','sequences'=>'séquences',
            'serie'=>'série','series'=>'séries','significativite'=>'significativité',
            'specifique'=>'spécifique','specifiques'=>'spécifiques','specifiquement'=>'spécifiquement',
            'supervisee'=>'supervisée','supplementaire'=>'supplémentaire',
            'systeme'=>'système','systemes'=>'systèmes','terminee'=>'terminée',
            'verifier'=>'vérifier','verifie'=>'vérifié','verifiee'=>'vérifiée','verifiez'=>'vérifiez',
            'video'=>'vidéo','individualise'=>'individualisé',
            'activee'=>'activée','configuree'=>'configurée','enregistree'=>'enregistrée','affichees'=>'affichées',
            'accompagnee'=>'accompagnée','expliquee'=>'expliquée','souhaitee'=>'souhaitée',
            'cloture'=>'clôture','age'=>'âge','pret'=>'prêt',
            'intermediaires'=>'intermédiaires','intermediaire'=>'intermédiaire',
            'ulterieure'=>'ultérieure','boite'=>'boîte','oppose'=>'opposé',
            'apparait'=>'apparaît','croise'=>'croisé','croises'=>'croisés','croisee'=>'croisée',
            'observe'=>'observé','observes'=>'observés','independance'=>'indépendance','hypothese'=>'hypothèse',
            'degre'=>'degré','degres'=>'degrés','liberte'=>'liberté',
            'inferieur'=>'inférieur','lineaire'=>'linéaire','basee'=>'basée',
            'dimensionnalite'=>'dimensionnalité','homogenes'=>'homogènes',
            'caracteristiques'=>'caractéristiques','irreversible'=>'irréversible',
            'desactivee'=>'désactivée','desactivation'=>'désactivation',
            'demographiques'=>'démographiques','probleme'=>'problème','problemes'=>'problèmes',
            'depannage'=>'dépannage','acceder'=>'accéder','gerer'=>'gérer',
            'developpement'=>'développement','developper'=>'développer','developpe'=>'développé','developpee'=>'développée',
            'precedente'=>'précédente','precedentes'=>'précédentes',
            'protege'=>'protégé','proteger'=>'protéger','cle'=>'clé','cles'=>'clés',
            'tete'=>'tête','consequence'=>'conséquence','consequences'=>'conséquences',
            'efficacite'=>'efficacité','rapidite'=>'rapidité','compatibilite'=>'compatibilité',
            'responsabilite'=>'responsabilité','activite'=>'activité','activites'=>'activités',
            'realite'=>'réalité','priorite'=>'priorité','autorite'=>'autorité',
            'validite'=>'validité','complexite'=>'complexité','simplicite'=>'simplicité',
            'specialite'=>'spécialité','identite'=>'identité',
            'francais'=>'français','francaise'=>'française','francaises'=>'françaises',
            'facon'=>'façon','facons'=>'façons','concu'=>'conçu','concus'=>'conçus','concue'=>'conçue',
            'general'=>'général','generale'=>'générale','generaux'=>'généraux','generalement'=>'généralement',
            'ete'=>'été','enquete'=>'enquête','enquetes'=>'enquêtes',
            'requete'=>'requête','requetes'=>'requêtes','apercu'=>'aperçu',
            'arriere'=>'arrière','cote'=>'côté','cotes'=>'côtés',
            'synthese'=>'synthèse','syntheses'=>'synthèses',
            'recapitulatif'=>'récapitulatif','recapitulatifs'=>'récapitulatifs',
            'adaptees'=>'adaptées','adaptee'=>'adaptée','adapte'=>'adapté',
            'declenche'=>'déclenche','declencher'=>'déclencher','declenchera'=>'déclenchera',
            'ajoutee'=>'ajoutée','ajoutees'=>'ajoutées','modifie'=>'modifié','modifiee'=>'modifiée',
            'supprime'=>'supprimé','affiche'=>'affiche','afficher'=>'afficher',
            'genere'=>'génère','creees'=>'créées','modifiees'=>'modifiées','supprimees'=>'supprimées',
            'reutilise'=>'réutilisé','reutiliser'=>'réutiliser','predefinie'=>'prédéfinie',
            'embarque'=>'embarqué','embarquee'=>'embarquée','exploite'=>'exploite','exploiter'=>'exploiter',
            'elabore'=>'élabore','elaborer'=>'élaborer','elaboree'=>'élaborée',
            'elaboration'=>'élaboration','exploitation'=>'exploitation',
            'collecte'=>'collecte','collecter'=>'collecter','collectee'=>'collectée',
            'operent'=>'opèrent','opere'=>'opère','operer'=>'opérer',
            'preferent'=>'préfèrent','modere'=>'modère','moderent'=>'modèrent',
            'differencie'=>'différencie','differencier'=>'différencier',
            'recoltent'=>'récoltent','recolter'=>'récolter',
            'evalue'=>'évalue','evaluent'=>'évaluent','evaluer'=>'évaluer','evaluee'=>'évaluée',
            'considere'=>'considère','considerent'=>'considèrent',
            'deplace'=>'déplace','deplacent'=>'déplacent','deplacer'=>'déplacer',
            'remplace'=>'remplace','remplacer'=>'remplacer',
            'enleve'=>'enlève','enlevent'=>'enlèvent',
            'estime'=>'estime','estiment'=>'estiment',
            'compare'=>'compare','comparer'=>'comparer',
            'mesure'=>'mesure','mesurer'=>'mesurer',
            'calcule'=>'calcule','calculent'=>'calculent',
            'traite'=>'traite','traitent'=>'traitent','traiter'=>'traiter','traitement'=>'traitement',
            'explore'=>'explore','explorent'=>'explorent',
            'vehicule'=>'véhicule','vehicules'=>'véhicules',
            'accedez'=>'accédez','selectionnez'=>'sélectionnez',
            'rendez-vous'=>'rendez-vous',
            // Capitalized variants (start of sentence)
            'Etude'=>'Étude','Etudes'=>'Études','Ecran'=>'Écran','Ecrans'=>'Écrans',
            'Creation'=>'Création','Creer'=>'Créer','Cree'=>'Crée','Creee'=>'Créée','Creees'=>'Créées',
            'Presentation'=>'Présentation','Presenter'=>'Présenter',
            'Generation'=>'Génération','Generer'=>'Générer','Genere'=>'Génère',
            'Repondants'=>'Répondants','Repondant'=>'Répondant','Reponses'=>'Réponses','Reponse'=>'Réponse',
            'Recoit'=>'Reçoit','Specialisee'=>'Spécialisée','Specialise'=>'Spécialisé',
            'Realisee'=>'Réalisée','Realise'=>'Réalisé','Realisation'=>'Réalisation',
            'Reference'=>'Référence','References'=>'Références','Experience'=>'Expérience',
            'Periode'=>'Période','Periodes'=>'Périodes','Categorie'=>'Catégorie','Categories'=>'Catégories',
            'Modele'=>'Modèle','Modeles'=>'Modèles','Donnees'=>'Données','Donnee'=>'Donnée',
            'Methode'=>'Méthode','Methodes'=>'Méthodes','Numero'=>'Numéro',
            'Preference'=>'Préférence','Preferences'=>'Préférences','Precise'=>'Précise',
            'Premiere'=>'Première','Premieres'=>'Premières','Derniere'=>'Dernière','Dernieres'=>'Dernières',
            'Complete'=>'Complète','Completee'=>'Complétée','Completees'=>'Complétées',
            'Interet'=>'Intérêt','Meme'=>'Même','Memes'=>'Mêmes',
            'Theme'=>'Thème','Themes'=>'Thèmes','Theorie'=>'Théorie',
            'Scenario'=>'Scénario','Scenarios'=>'Scénarios','Resume'=>'Résumé',
            'Securite'=>'Sécurité','Qualite'=>'Qualité','Probabilite'=>'Probabilité',
            'Echelle'=>'Échelle','Echelles'=>'Échelles','Element'=>'Élément','Elements'=>'Éléments',
            'Fenetre'=>'Fenêtre','Fenetres'=>'Fenêtres','Apres'=>'Après','Tres'=>'Très',
            'Acces'=>'Accès','Succes'=>'Succès','Progres'=>'Progrès',
            'Telecharger'=>'Télécharger','Telechargement'=>'Téléchargement',
            'Interpretation'=>'Interprétation','Interpreter'=>'Interpréter',
            'Differents'=>'Différents','Different'=>'Différent','Differente'=>'Différente',
            'Avancee'=>'Avancée','Avancees'=>'Avancées','Utilisee'=>'Utilisée','Utilise'=>'Utilisé',
            'Fermee'=>'Fermée','Fermees'=>'Fermées','Deployee'=>'Déployée','Deploiement'=>'Déploiement',
            'Decrite'=>'Décrite','Decrire'=>'Décrire','Decrit'=>'Décrit',
            'Decision'=>'Décision','Decouvrir'=>'Découvrir','Dediee'=>'Dédiée',
            'Defaut'=>'Défaut','Defauts'=>'Défauts','Definir'=>'Définir','Deja'=>'Déjà',
            'Dependance'=>'Dépendance','Deroule'=>'Déroule','Deroulement'=>'Déroulement',
            'Detail'=>'Détail','Details'=>'Détails','Detaille'=>'Détaillé','Detaillee'=>'Détaillée',
            'Determination'=>'Détermination','Determiner'=>'Déterminer',
            'Disponibilite'=>'Disponibilité','Eboulis'=>'Éboulis','Empeche'=>'Empêche',
            'Enregistre'=>'Enregistré','Entree'=>'Entrée','Entrees'=>'Entrées','Estimee'=>'Estimée',
            'Etape'=>'Étape','Etapes'=>'Étapes','Etat'=>'État','Etats'=>'États',
            'Etait'=>'Était','Etaient'=>'Étaient','Etant'=>'Étant',
            'Eviter'=>'Éviter','Evite'=>'Évite','Exterieur'=>'Extérieur',
            'Facon'=>'Façon','Facon'=>'Façon',
            'Fonctionnalite'=>'Fonctionnalité','Fonctionnalites'=>'Fonctionnalités',
            'Hierarchique'=>'Hiérarchique','Integre'=>'Intégré','Integree'=>'Intégrée',
            'Integration'=>'Intégration','Intitule'=>'Intitulé',
            'Lateral'=>'Latéral','Laterale'=>'Latérale','Legende'=>'Légende',
            'Libelle'=>'Libellé','Libelles'=>'Libellés','Limitee'=>'Limitée',
            'Marche'=>'Marché','Marches'=>'Marchés','Matiere'=>'Matière','Matieres'=>'Matières',
            'Modalite'=>'Modalité','Modalites'=>'Modalités',
            'Necessaire'=>'Nécessaire','Necessite'=>'Nécessite','Necessiter'=>'Nécessiter',
            'Numerique'=>'Numérique','Numeriques'=>'Numériques',
            'Operation'=>'Opération','Operations'=>'Opérations',
            'Parametre'=>'Paramètre','Parametres'=>'Paramètres','Parametrage'=>'Paramétrage',
            'Particuliere'=>'Particulière','Particulierement'=>'Particulièrement',
            'Passe'=>'Passé','Preparation'=>'Préparation','Preparer'=>'Préparer',
            'Previsualisation'=>'Prévisualisation','Previsualiser'=>'Prévisualiser',
            'Procedural'=>'Procédural','Procedure'=>'Procédure','Procedures'=>'Procédures',
            'Recolte'=>'Récolte','Redaction'=>'Rédaction','Rediger'=>'Rédiger',
            'Reduire'=>'Réduire','Reduit'=>'Réduit','Reduite'=>'Réduite',
            'Regle'=>'Règle','Regles'=>'Règles','Repartition'=>'Répartition',
            'Representer'=>'Représenter','Represente'=>'Représente','Representent'=>'Représentent',
            'Reseaux'=>'Réseaux','Resolution'=>'Résolution','Resultat'=>'Résultat','Resultats'=>'Résultats',
            'Reunion'=>'Réunion','Reunions'=>'Réunions','Reussite'=>'Réussite',
            'Reinitialiser'=>'Réinitialiser','Reserve'=>'Réservé',
            'Reapparait'=>'Réapparaît','Reapparaissent'=>'Réapparaissent',
            'Role'=>'Rôle','Roles'=>'Rôles','Schema'=>'Schéma','Schemas'=>'Schémas',
            'Selection'=>'Sélection','Selectionner'=>'Sélectionner','Selectionne'=>'Sélectionné',
            'Separateur'=>'Séparateur','Separation'=>'Séparation','Sequence'=>'Séquence','Sequences'=>'Séquences',
            'Serie'=>'Série','Series'=>'Séries','Significativite'=>'Significativité',
            'Specifique'=>'Spécifique','Specifiques'=>'Spécifiques',
            'Supervisee'=>'Supervisée','Supplementaire'=>'Supplémentaire',
            'Systeme'=>'Système','Systemes'=>'Systèmes','Terminee'=>'Terminée',
            'Verifier'=>'Vérifier','Verifie'=>'Vérifié','Verifiee'=>'Vérifiée','Verifiez'=>'Vérifiez',
            'Video'=>'Vidéo','Activee'=>'Activée','Configuree'=>'Configurée',
            'Enregistree'=>'Enregistrée','Affichees'=>'Affichées',
            'Cloture'=>'Clôture','Age'=>'Âge','Pret'=>'Prêt',
            'Intermediaires'=>'Intermédiaires','Intermediaire'=>'Intermédiaire',
            'Ulterieure'=>'Ultérieure','Boite'=>'Boîte','Oppose'=>'Opposé',
            'Apparait'=>'Apparaît','Croise'=>'Croisé','Croises'=>'Croisés','Croisee'=>'Croisée',
            'Observe'=>'Observé','Observes'=>'Observés','Independance'=>'Indépendance','Hypothese'=>'Hypothèse',
            'Degre'=>'Degré','Degres'=>'Degrés','Liberte'=>'Liberté',
            'Inferieur'=>'Inférieur','Lineaire'=>'Linéaire','Basee'=>'Basée',
            'Homogenes'=>'Homogènes','Caracteristiques'=>'Caractéristiques',
            'Desactivee'=>'Désactivée','Desactivation'=>'Désactivation',
            'Demographiques'=>'Démographiques','Probleme'=>'Problème','Problemes'=>'Problèmes',
            'Depannage'=>'Dépannage','Acceder'=>'Accéder','Gerer'=>'Gérer',
            'Developpement'=>'Développement','Developper'=>'Développer','Developpe'=>'Développé',
            'Precedente'=>'Précédente','Precedentes'=>'Précédentes',
            'Protege'=>'Protégé','Proteger'=>'Protéger','Cle'=>'Clé','Cles'=>'Clés',
            'Tete'=>'Tête','Consequence'=>'Conséquence','Consequences'=>'Conséquences',
            'Efficacite'=>'Efficacité','Rapidite'=>'Rapidité','Compatibilite'=>'Compatibilité',
            'Responsabilite'=>'Responsabilité','Activite'=>'Activité','Activites'=>'Activités',
            'Realite'=>'Réalité','Priorite'=>'Priorité','Autorite'=>'Autorité',
            'Validite'=>'Validité','Complexite'=>'Complexité','Simplicite'=>'Simplicité',
            'Specialite'=>'Spécialité','Identite'=>'Identité',
            'Francais'=>'Français','Francaise'=>'Française','Francaises'=>'Françaises',
            'Concu'=>'Conçu','Concus'=>'Conçus','Concue'=>'Conçue',
            'General'=>'Général','Generale'=>'Générale','Generaux'=>'Généraux',
            'Ete'=>'Été','Enquete'=>'Enquête','Enquetes'=>'Enquêtes',
            'Requete'=>'Requête','Requetes'=>'Requêtes','Apercu'=>'Aperçu',
            'Arriere'=>'Arrière','Cote'=>'Côté','Cotes'=>'Côtés',
            'Synthese'=>'Synthèse','Syntheses'=>'Synthèses',
            'Recapitulatif'=>'Récapitulatif','Adaptees'=>'Adaptées','Adaptee'=>'Adaptée',
            'Declenche'=>'Déclenche','Declencher'=>'Déclencher',
            'Ajoutee'=>'Ajoutée','Ajoutees'=>'Ajoutées','Modifie'=>'Modifié','Modifiee'=>'Modifiée',
            'Supprime'=>'Supprimé','Affiche'=>'Affiche','Afficher'=>'Afficher',
            'Genere'=>'Génère','Modifiees'=>'Modifiées','Supprimees'=>'Supprimées',
            'Reutilise'=>'Réutilisé','Predefinie'=>'Prédéfinie',
            'Embarque'=>'Embarqué','Embarquee'=>'Embarquée',
            'Elabore'=>'Élabore','Elaborer'=>'Élaborer','Elaboree'=>'Élaborée',
            'Elaboration'=>'Élaboration','Exploitation'=>'Exploitation',
            'Collecte'=>'Collecte','Collecter'=>'Collecter','Collectee'=>'Collectée',
            'Operent'=>'Opèrent','Opere'=>'Opère','Operer'=>'Opérer',
            'Preferent'=>'Préfèrent','Modere'=>'Modère','Moderent'=>'Modèrent',
            'Differencie'=>'Différencie','Differencier'=>'Différencier',
            'Recoltent'=>'Récoltent','Recolter'=>'Récolter',
            'Evalue'=>'Évalue','Evaluent'=>'Évaluent','Evaluer'=>'Évaluer','Evaluee'=>'Évaluée',
            'Considere'=>'Considère','Considerent'=>'Considèrent',
            'Deplace'=>'Déplace','Deplacent'=>'Déplacent','Deplacer'=>'Déplacer',
            'Remplace'=>'Remplace','Remplacer'=>'Remplacer',
            'Enleve'=>'Enlève','Enlevent'=>'Enlèvent',
            'Estime'=>'Estime','Estiment'=>'Estiment',
            'Compare'=>'Compare','Comparer'=>'Comparer',
            'Mesure'=>'Mesure','Mesurer'=>'Mesurer',
            'Calcule'=>'Calcule','Calculent'=>'Calculent',
            'Traite'=>'Traite','Traitent'=>'Traitent','Traiter'=>'Traiter','Traitement'=>'Traitement',
            'Explore'=>'Explore','Explorent'=>'Explorent',
            'Vehicule'=>'Véhicule','Vehicules'=>'Véhicules',
            'Accedez'=>'Accédez','Selectionnez'=>'Sélectionnez',
            'Adapte'=>'Adapté','Adaptees'=>'Adaptées',
            'Echantillonnage'=>'Échantillonnage','Echantillon'=>'Échantillon',
            'Etudiants'=>'Étudiants','Etudiant'=>'Étudiant',
            'Specialisee'=>'Spécialisée','Specialise'=>'Spécialisé','Specialises'=>'Spécialisés',
            'Passe'=>'Passé','Preselectionne'=>'Présélectionné',
            'Integres'=>'Intégrés','Journaliere'=>'Journalière',
            'Detailles'=>'Détaillés','Detecter'=>'Détecter',
            'Individualise'=>'Individualisé',
            'Accompagnee'=>'Accompagnée','Expliquee'=>'Expliquée','Souhaitee'=>'Souhaitée',
            'Irreversible'=>'Irréversible',
            'Generees'=>'Générées','Creees'=>'Créées',
            'Utilisees'=>'Utilisées','Deployees'=>'Déployées',
            'Realisees'=>'Réalisées','Terminees'=>'Terminées',
            'Cloturees'=>'Clôturées','Souhaitees'=>'Souhaitées',
            'Proposees'=>'Proposées','Recuperees'=>'Récupérées',
            'Estimees'=>'Estimées','Calculees'=>'Calculées',
            'Comparees'=>'Comparées','Mesurees'=>'Mesurées',
            'Verifiees'=>'Vérifiées','Testees'=>'Testées',
            'Evaluees'=>'Évaluées','Exploitees'=>'Exploitées',
            'Traitees'=>'Traitées','Deplacees'=>'Déplacées',
            'Remplacees'=>'Remplacées','Ajoutees'=>'Ajoutées',
            'Retirees'=>'Retirées','Enlevees'=>'Enlevées',
            'Collectees'=>'Collectées','Recoltees'=>'Récoltées',
            'Enregistrees'=>'Enregistrées','Configurees'=>'Configurées',
            'Activees'=>'Activées','Desactivees'=>'Désactivées',
            'Fermees'=>'Fermées','Affichees'=>'Affichées',
            'Dimensionnalite'=>'Dimensionnalité','Specifiquement'=>'Spécifiquement',
            'Reserve'=>'Réservé','Passe'=>'Passé',
        ];
        // Preposition "à" phrases
        $a_phrases = [
            'a l\'aide'=>'à l\'aide','a la'=>'à la','a partir'=>'à partir','a chaque'=>'à chaque',
            'a son'=>'à son','a leur'=>'à leur','a votre'=>'à votre','a notre'=>'à notre',
            'a propos'=>'à propos','a distance'=>'à distance','a condition'=>'à condition',
            'a l\'interieur'=>'à l\'intérieur','a l\'exterieur'=>'à l\'extérieur',
            'a l\'inverse'=>'à l\'inverse','a l\'origine'=>'à l\'origine',
            'a l\'avenir'=>'à l\'avenir','a l\'ecoute'=>'à l\'écoute',
            'a l\'image'=>'à l\'image','a l\'etude'=>'à l\'étude',
            'a l\'echelle'=>'à l\'échelle','a l\'exception'=>'à l\'exception',
            'a l\'oppose'=>'à l\'opposé','a l\'egard'=>'à l\'égard',
            'a l\'encontre'=>'à l\'encontre','a l\'arriere'=>'à l\'arrière',
            'a l\'issue'=>'à l\'issue','a l\'instar'=>'à l\'instar',
            'a l\'attention'=>'à l\'attention','a l\'heure'=>'à l\'heure',
            'a l\'ecran'=>'à l\'écran','a l\'ecart'=>'à l\'écart',
            'a l\'etat'=>'à l\'état','a l\'etape'=>'à l\'étape',
            'a l\'ecole'=>'à l\'école',
        ];
        $tr = array_merge($tr, $a_phrases);
    }
    foreach ($tr as $from => $to) {
        $text = preg_replace('/\b' . preg_quote($from, '/') . '\b/u', $to, $text);
    }
    return $text;
}

function w_esc($text) {
    return htmlspecialchars(fr_accents($text), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function w_run($text, $opts = []) {
    $bold = $opts['bold'] ?? false;
    $italic = $opts['italic'] ?? false;
    $size = $opts['size'] ?? null;
    $color = $opts['color'] ?? null;
    $font = $opts['font'] ?? 'Inter';
    $rpr = '';
    if ($bold) $rpr .= '<w:b/>';
    if ($italic) $rpr .= '<w:i/>';
    if ($size) $rpr .= '<w:sz w:val="' . ($size * 2) . '"/>';
    if ($color) $rpr .= '<w:color w:val="' . $color . '"/>';
    if ($font) $rpr .= '<w:rFonts w:ascii="' . $font . '" w:hAnsi="' . $font . '"/>';
    return '<w:r><w:rPr>' . $rpr . '</w:rPr><w:t xml:space="preserve">' . w_esc($text) . '</w:t></w:r>';
}

function w_p($runs, $opts = []) {
    $style = $opts['style'] ?? null;
    $align = $opts['align'] ?? null;
    $spacing = $opts['spacing'] ?? null;
    $ppr = '';
    if ($style) $ppr .= '<w:pStyle w:val="' . $style . '"/>';
    if ($align) $ppr .= '<w:jc w:val="' . $align . '"/>';
    if ($spacing) $ppr .= '<w:spacing w:after="' . $spacing . '"/>';
    return '<w:p><w:pPr>' . $ppr . '</w:pPr>' . $runs . '</w:p>';
}

function w_h1($text, $opts = []) {
    $color = $opts['color'] ?? '4F46E5';
    $runs = w_run($text, ['bold' => true, 'size' => 18, 'color' => $color]);
    return w_p($runs, ['style' => 'Heading1', 'spacing' => 240]);
}

function w_h2($text, $opts = []) {
    $color = $opts['color'] ?? '111827';
    $runs = w_run($text, ['bold' => true, 'size' => 15, 'color' => $color]);
    return w_p($runs, ['style' => 'Heading2', 'spacing' => 200]);
}

function w_h3($text) {
    $runs = w_run($text, ['bold' => true, 'size' => 13, 'color' => '374151']);
    return w_p($runs, ['style' => 'Heading3', 'spacing' => 160]);
}

function w_para($text, $opts = []) {
    $p_opts = ['spacing' => 120];
    if (isset($opts['align'])) $p_opts['align'] = $opts['align'];
    if (isset($opts['style'])) $p_opts['style'] = $opts['style'];
    $runs = w_run($text, $opts);
    return w_p($runs, $p_opts);
}

function w_bold_para($label, $text, $opts = []) {
    $runs = w_run($label, ['bold' => true]) . w_run($text);
    return w_p($runs, ['spacing' => 120] + $opts);
}

function w_bullet($text, $opts = []) {
    $bold_prefix = $opts['bold_prefix'] ?? null;
    $runs = '';
    if ($bold_prefix) {
        $runs .= w_run($bold_prefix, ['bold' => true]);
    }
    $runs .= w_run($text);
    return '<w:p><w:pPr><w:pStyle w:val="ListBullet"/><w:spacing w:after="60"/></w:pPr>' . $runs . '</w:p>';
}

function w_page_break() {
    return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
}

function w_table($headers, $rows, $opts = []) {
    $width = $opts['width'] ?? 9000;
    $header_bg = $opts['header_bg'] ?? '4F46E5';
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="' . $width . '" w:type="dxa"/><w:tblBorders>' .
        '<w:top w:val="single" w:sz="4" w:color="D1D5DB"/>' .
        '<w:left w:val="single" w:sz="4" w:color="D1D5DB"/>' .
        '<w:bottom w:val="single" w:sz="4" w:color="D1D5DB"/>' .
        '<w:right w:val="single" w:sz="4" w:color="D1D5DB"/>' .
        '<w:insideH w:val="single" w:sz="4" w:color="E5E7EB"/>' .
        '<w:insideV w:val="single" w:sz="4" w:color="E5E7EB"/>' .
        '</w:tblBorders></w:tblPr>';

    // Header row
    $xml .= '<w:tr>';
    foreach ($headers as $h) {
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . intval($width / count($headers)) . '" w:type="dxa"/>' .
            '<w:shd w:val="clear" w:fill="' . $header_bg . '"/></w:tcPr>' .
            w_p(w_run($h, ['bold' => true, 'size' => 11, 'color' => 'FFFFFF']), ['spacing' => 40]) .
            '</w:tc>';
    }
    $xml .= '</w:tr>';

    // Data rows
    foreach ($rows as $row) {
        $xml .= '<w:tr>';
        foreach ($row as $cell) {
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . intval($width / count($headers)) . '" w:type="dxa"/></w:tcPr>' .
                w_p(w_run($cell, ['size' => 11]), ['spacing' => 40]) . '</w:tc>';
        }
        $xml .= '</w:tr>';
    }
    $xml .= '</w:tbl>';
    return $xml . w_p('', ['spacing' => 120]);
}

function w_info_box($label, $text, $bg = 'EFF6FF', $border_color = 'BFDBFE') {
    $runs = w_run($label . ' ', ['bold' => true, 'color' => '111827']) . w_run($text);
    return '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>' .
        '<w:top w:val="single" w:sz="8" w:color="' . $border_color . '"/>' .
        '<w:left w:val="single" w:sz="8" w:color="' . $border_color . '"/>' .
        '<w:bottom w:val="single" w:sz="8" w:color="' . $border_color . '"/>' .
        '<w:right w:val="single" w:sz="8" w:color="' . $border_color . '"/>' .
        '</w:tblBorders><w:shd w:val="clear" w:fill="' . $bg . '"/></w:tblPr>' .
        '<w:tr><w:tc><w:tcPr><w:tcW w:w="9000" w:type="dxa"/><w:shd w:val="clear" w:fill="' . $bg . '"/></w:tcPr>' .
        w_p($runs, ['spacing' => 80]) . '</w:tc></w:tr></w:tbl>' . w_p('', ['spacing' => 120]);
}

function w_cover_page($title, $subtitle, $project, $version) {
    $xml = '';
    // Spacer
    for ($i = 0; $i < 6; $i++) $xml .= w_p('', ['spacing' => 200]);
    // Title
    $xml .= w_p(w_run($title, ['bold' => true, 'size' => 36, 'color' => '4F46E5']), ['align' => 'center', 'spacing' => 200]);
    // Subtitle
    $xml .= w_p(w_run($subtitle, ['size' => 16, 'color' => '6B7280']), ['align' => 'center', 'spacing' => 400]);
    // Project info
    $xml .= w_p(w_run('Projet', ['size' => 10, 'color' => '9CA3AF']), ['align' => 'center', 'spacing' => 0]);
    $xml .= w_p(w_run($project, ['bold' => true, 'size' => 14, 'color' => '111827']), ['align' => 'center', 'spacing' => 600]);
    // Version
    $xml .= w_p(w_run($version, ['size' => 11, 'color' => '9CA3AF']), ['align' => 'center', 'spacing' => 0]);
    $xml .= w_page_break();
    return $xml;
}

function w_toc($items) {
    // Title
    $xml = w_p(w_run('Table des matières', ['bold' => true, 'size' => 20, 'color' => '4F46E5']), ['spacing' => 300]);

    // Word native TOC field - auto-generates entries with page numbers from Heading1-3 styles
    $xml .= '<w:p><w:pPr><w:spacing w:after="120"/></w:pPr>';
    $xml .= '<w:r><w:fldChar w:fldCharType="begin" w:dirty="true"/></w:r>';
    $xml .= '<w:r><w:instrText xml:space="preserve">TOC \o "1-3" \h \z \u</w:instrText></w:r>';
    $xml .= '<w:r><w:fldChar w:fldCharType="separate"/></w:r>';
    // Placeholder text shown until user updates the field (Word will prompt to update on open)
    $xml .= '<w:r><w:rPr><w:rFonts w:ascii="Inter" w:hAnsi="Inter"/><w:color w:val="9CA3AF"/><w:sz w:val="20"/></w:rPr>';
    $xml .= '<w:t xml:space="preserve">Faites un clic droit ici et sélectionnez « Mettre à jour les champs » pour générer la table des matières avec les numéros de page.</w:t></w:r>';
    $xml .= '<w:r><w:fldChar w:fldCharType="end"/></w:r>';
    $xml .= '</w:p>';

    $xml .= w_page_break();
    return $xml;
}

function build_docx($body_xml, $output_path) {
    $zip = new ZipArchive();
    if ($zip->open($output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die('Impossible de creer le fichier .docx');
    }

    // [Content_Types].xml
    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
        '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>' .
        '</Types>');

    // _rels/.rels
    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
        '</Relationships>');

    // word/styles.xml
    $zip->addFromString('word/styles.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
        '<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Inter" w:hAnsi="Inter"/><w:sz w:val="22"/></w:rPr></w:rPrDefault></w:docDefaults>' .
        '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:pPr><w:spacing w:before="360" w:after="120"/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:sz w:val="36"/></w:rPr></w:style>' .
        '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:pPr><w:spacing w:before="280" w:after="100"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr><w:b/><w:sz w:val="30"/></w:rPr></w:style>' .
        '<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/><w:pPr><w:spacing w:before="200" w:after="80"/><w:outlineLvl w:val="2"/></w:pPr><w:rPr><w:b/><w:sz w:val="26"/></w:rPr></w:style>' .
        '<w:style w:type="paragraph" w:styleId="ListBullet"><w:name w:val="List Bullet"/><w:pPr><w:ind w:left="720"/><w:spacing w:after="60"/></w:pPr></w:style>' .
        '</w:styles>');

    // word/document.xml
    $document_xml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
        '<w:body>' . $body_xml .
        '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>' .
        '</w:body></w:document>';
    $zip->addFromString('word/document.xml', $document_xml);

    $zip->close();
}

// ===== Manuel Utilisateur Content =====

function build_manuel_content() {
    $xml = '';
    $xml .= w_cover_page('Manuel Utilisateur', 'Plateforme web d\'etudes de marche avec enquetes en ligne et analyses statistiques', 'N 69 - MarketStudy Pro', 'Version 1.0 - ' . date('Y'));
    $xml .= w_toc([
        'Presentation de la plateforme',
        'Les trois acteurs et leurs roles',
        'Premiers pas : inscription et connexion',
        'Tableau de bord',
        'Creer une etude de marche',
        'Construire le questionnaire',
        'Types de questions',
        'Controles de coherence et sauts conditionnels',
        'Distribution de l\'enquete',
        'Participation des repondants',
        'Tris a plat et tris croises',
        'Tests statistiques (Khi2, t-Student, ANOVA)',
        'Correlations de Pearson et Spearman',
        'Analyse en Composantes Principales (ACP)',
        'Classification (K-means et CAH)',
        'Calcul de la taille d\'echantillon',
        'Generation du rapport et export PDF',
        'Gestion des utilisateurs (administrateur)',
        'Interpretation automatique des resultats',
        'FAQ et depannage',
    ]);

    // Section 1
    $xml .= w_h1('1. Presentation de la plateforme');
    $xml .= w_para('MarketStudy Pro est une plateforme web integree couvrant l\'ensemble du cycle d\'une etude de marche : de la construction du questionnaire a la distribution, en passant par la collecte des reponses, l\'analyse statistique avancee et la generation de rapports exportables en PDF.');
    $xml .= w_para('La plateforme s\'adresse aux chercheurs en marketing, etudiants et professionnels qui souhaitent conduire des etudes de marche rigoureuses avec des outils statistiques de pointe, sans necessiter de connaissances en programmation.');
    $xml .= w_h3('Modules principaux');
    $xml .= w_bullet('Gestion des etudes : creation, parametrage et cycle de vie', ['bold_prefix' => '']);
    $xml .= w_bullet('Constructeur de questionnaire : sections, questions, options, sauts conditionnels');
    $xml .= w_bullet('Distribution : lien direct, QR code, invitations par email');
    $xml .= w_bullet('Analyses statistiques : tris, tests, ACP, classification');
    $xml .= w_bullet('Rapports : generation automatique avec graphiques et export PDF');
    $xml .= w_para('[Capture d\'ecran - Figure 1 : Page d\'accueil publique (landing page)]', ['italic' => true, 'color' => '9CA3AF']);

    // Section 2
    $xml .= w_h1('2. Les trois acteurs et leurs roles');
    $xml .= w_para('MarketStudy Pro definit trois roles distincts avec des permissions adaptees a chaque profil d\'utilisateur.');
    $xml .= w_h3('Tableau des permissions');
    $xml .= w_table(
        ['Fonctionnalite', 'Admin', 'Chercheur', 'Repondant'],
        [
            ['Tableau de bord', 'Toutes les etudes', 'Ses etudes', 'Vue limitee'],
            ['Creer une etude', 'Oui', 'Oui', '-'],
            ['Construire un questionnaire', 'Oui', 'Oui', '-'],
            ['Distribuer une enquete', 'Oui', 'Oui', '-'],
            ['Analyses statistiques', 'Oui', 'Oui', '-'],
            ['Generer un rapport', 'Oui', 'Oui', '-'],
            ['Gerer les utilisateurs', 'Oui', '-', '-'],
            ['Participer aux enquetes', '-', '-', 'Oui'],
        ]
    );
    $xml .= w_info_box('Note : ', 'L\'inscription en tant qu\'administrateur n\'est pas possible depuis le formulaire public. Seuls les administrateurs existants peuvent creer d\'autres comptes admin.');

    // Section 3
    $xml .= w_h1('3. Premiers pas : inscription et connexion');
    $xml .= w_h3('Inscription');
    $xml .= w_bullet('Rendez-vous sur la page d\'accueil et cliquez sur "S\'inscrire".', ['bold_prefix' => 'Etape 1 : ']);
    $xml .= w_bullet('Saisissez votre prenom, nom, adresse email, mot de passe (minimum 6 caracteres) et choisissez votre role : Repondant ou Chercheur.', ['bold_prefix' => 'Etape 2 : ']);
    $xml .= w_bullet('Cliquez sur "Creer mon compte". Vous serez automatiquement connecte et redirige vers le tableau de bord.', ['bold_prefix' => 'Etape 3 : ']);
    $xml .= w_para('[Capture d\'ecran - Figure 2 : Formulaire d\'inscription avec selection du role]', ['italic' => true, 'color' => '9CA3AF']);
    $xml .= w_h3('Connexion');
    $xml .= w_para('Sur la page de connexion, saisissez votre adresse email et votre mot de passe. Vous pouvez egalement utiliser les comptes de demonstration en cliquant directement sur l\'un des comptes affiches.');
    $xml .= w_info_box('Astuce : ', 'Comptes de demonstration - admin@marketstudy.com / admin123, chercheur@marketstudy.com / chercheur123, repondant@marketstudy.com / repondant123.', 'ECFDF5', 'A7F3D0');

    // Section 4
    $xml .= w_h1('4. Tableau de bord');
    $xml .= w_para('Le tableau de bord est la page d\'accueil apres connexion. Il affiche un resume des activites selon votre role :');
    $xml .= w_bullet('Statistiques globales et liste de toutes les etudes', ['bold_prefix' => 'Administrateur : ']);
    $xml .= w_bullet('Statistiques de ses propres etudes', ['bold_prefix' => 'Chercheur : ']);
    $xml .= w_bullet('Vue simplifiee avec acces aux enquetes', ['bold_prefix' => 'Repondant : ']);
    $xml .= w_para('[Capture d\'ecran - Figure 3 : Tableau de bord avec statistiques et etudes recentes]', ['italic' => true, 'color' => '9CA3AF']);
    $xml .= w_h3('Navigation');
    $xml .= w_para('La barre laterale permet d\'acceder aux modules : Tableau de bord, Mes etudes, Analyses, Echantillonnage, Rapports, Utilisateurs (admin).');

    // Section 5
    $xml .= w_h1('5. Creer une etude de marche');
    $xml .= w_para('Cliquez sur "Mes etudes" puis "Nouvelle etude". Renseignez les champs suivants :');
    $xml .= w_table(
        ['Champ', 'Description', 'Obligatoire'],
        [
            ['Titre', 'Nom de l\'etude', 'Oui'],
            ['Description', 'Objectif et contexte', 'Non'],
            ['Domaine', 'Domaine d\'application', 'Non'],
            ['Taille cible', 'Nombre de repondants vises', 'Non (defaut : 100)'],
            ['Methode d\'echantillonnage', 'Aleatoire simple, stratifie, quotas, convenance', 'Non'],
            ['Marge d\'erreur', 'Precision (typiquement 5%)', 'Non'],
            ['Niveau de confiance', 'Probabilite (typiquement 95%)', 'Non'],
        ]
    );
    $xml .= w_para('[Capture d\'ecran - Figure 4 : Formulaire de creation avec parametres d\'echantillonnage]', ['italic' => true, 'color' => '9CA3AF']);
    $xml .= w_h3('Cycle de vie');
    $xml .= w_bullet('Configuration en cours', ['bold_prefix' => 'Brouillon : ']);
    $xml .= w_bullet('Ouverte a la participation', ['bold_prefix' => 'Active : ']);
    $xml .= w_bullet('Collecte close, analyses disponibles', ['bold_prefix' => 'Terminee : ']);

    // Section 6
    $xml .= w_h1('6. Construire le questionnaire');
    $xml .= w_para('Accedez au constructeur depuis la page de detail de l\'etude. Le constructeur permet d\'organiser le questionnaire en sections et d\'ajouter des questions a chaque section.');
    $xml .= w_bullet('Cliquez sur "+ Section". Donnez un titre et une description optionnelle.', ['bold_prefix' => 'Etape 1 : ']);
    $xml .= w_bullet('Cliquez sur "+ Question" dans la section souhaitee. Saisissez le libelle, choisissez le type, et ajoutez les options de reponse.', ['bold_prefix' => 'Etape 2 : ']);
    $xml .= w_bullet('Cliquez sur "Apercu" pour voir le questionnaire tel qu\'il apparaitra aux repondants.', ['bold_prefix' => 'Etape 3 : ']);
    $xml .= w_para('[Capture d\'ecran - Figure 5 : Sections et questions du questionnaire]', ['italic' => true, 'color' => '9CA3AF']);

    // Section 7
    $xml .= w_h1('7. Types de questions');
    $xml .= w_para('MarketStudy Pro supporte 7 types de questions :');
    $xml .= w_table(
        ['Type', 'Description', 'Exemple'],
        [
            ['Fermee (choix unique)', 'L\'utilisateur selectionne une seule reponse', 'Quel est votre genre ?'],
            ['Fermee (choix multiple)', 'L\'utilisateur peut selectionner plusieurs reponses', 'Quelles marques connaissez-vous ?'],
            ['Echelle de Likert', '5 niveaux d\'accord', 'Etes-vous satisfait du produit ?'],
            ['Echelle numerique', 'Curseur de min a max avec libelles', 'Notez de 1 a 10'],
            ['Question ouverte', 'Zone de texte libre', 'Quelles sont vos suggestions ?'],
            ['Question numerique', 'Saisie d\'un nombre', 'Quel est votre age ?'],
            ['Classement', 'Glisser-deposer pour ordonner des options', 'Classez ces criteres par importance'],
        ]
    );

    // Section 8
    $xml .= w_h1('8. Controles de coherence et sauts conditionnels');
    $xml .= w_para('Les sauts conditionnels permettent d\'adapter le parcours du repondant en fonction de ses reponses. Par exemple, si le repondant repond "Non" a "Possedez-vous un vehicule ?", le questionnaire peut sauter directement a une question ulterieure.');
    $xml .= w_h3('Configuration');
    $xml .= w_bullet('Le saut conditionnel est disponible uniquement pour les questions de type "Fermee (choix unique)".', ['bold_prefix' => 'Etape 1 : ']);
    $xml .= w_bullet('Dans la section "Saut conditionnel" du formulaire, selectionnez l\'option qui declenchera le saut, puis choisissez la question de destination.', ['bold_prefix' => 'Etape 2 : ']);
    $xml .= w_bullet('La regle est enregistree. Un badge "Saut" apparait sur la question dans le constructeur.', ['bold_prefix' => 'Etape 3 : ']);
    $xml .= w_info_box('Attention : ', 'Le saut masque toutes les questions entre la question actuelle et la question de destination. Si le repondant change sa reponse, les questions reapparaissent automatiquement.', 'FFFBEB', 'FDE68A');

    // Section 9
    $xml .= w_h1('9. Distribution de l\'enquete');
    $xml .= w_para('Une fois le questionnaire pret et l\'etude activee (statut "Active"), accedez au module Distribution depuis la page de detail de l\'etude.');
    $xml .= w_h3('Trois modes de distribution');
    $xml .= w_bullet('URL a partager manuellement. Copiez le lien ou ouvrez-le dans un nouvel onglet.', ['bold_prefix' => 'Lien direct : ']);
    $xml .= w_bullet('Image telechargeable a afficher sur supports physiques. Scannable avec un smartphone.', ['bold_prefix' => 'QR code : ']);
    $xml .= w_bullet('Saisir une liste d\'adresses (une par ligne). Chaque repondant recoit un token unique.', ['bold_prefix' => 'Invitations par email : ']);
    $xml .= w_para('[Capture d\'ecran - Figure 7 : Lien, QR code et invitations par email]', ['italic' => true, 'color' => '9CA3AF']);
    $xml .= w_info_box('Tokens uniques : ', 'Chaque invitation genere un token unique qui permet de suivre le statut de chaque repondant (invite, en cours, termine, abandon).');

    // Section 10
    $xml .= w_h1('10. Participation des repondants');
    $xml .= w_para('Les repondants accedent au questionnaire via le lien de participation ou le QR code. Aucune inscription n\'est requise pour participer.');
    $xml .= w_h3('Parcours du repondant');
    $xml .= w_bullet('Titre de l\'etude, description et barre de progression.', ['bold_prefix' => 'Etape 1 : ']);
    $xml .= w_bullet('Informations demographiques optionnelles (age, genre, ville, profession).', ['bold_prefix' => 'Etape 2 : ']);
    $xml .= w_bullet('Navigation intuitive avec differents types de widgets (radio, checkbox, curseur, texte, classement).', ['bold_prefix' => 'Etape 3 : ']);
    $xml .= w_bullet('Page de remerciement confirmant la participation.', ['bold_prefix' => 'Etape 4 : ']);

    // Section 11
    $xml .= w_h1('11. Tris a plat et tris croises');
    $xml .= w_h3('Tris a plat');
    $xml .= w_para('Les tris a plat affichent les effectifs et pourcentages pour chaque modalite d\'une question. Accessibles via Analyses > Tris a plat.');
    $xml .= w_bullet('Tableau d\'effectifs avec pourcentages');
    $xml .= w_bullet('Graphique en barres (Chart.js)');
    $xml .= w_bullet('Statistiques descriptives pour les variables numeriques (moyenne, mediane, ecart-type)');
    $xml .= w_bullet('Affichage des reponses ouvertes');
    $xml .= w_bullet('Interpretation automatique');
    $xml .= w_h3('Tris croises');
    $xml .= w_para('Les tris croises permettent de croiser deux variables pour afficher un tableau de contingence. Selectionnez deux questions et visualisez la repartition croisee des effectifs.');

    // Section 12
    $xml .= w_h1('12. Tests statistiques (Khi2, t-Student, ANOVA)');
    $xml .= w_h3('Test du Khi2');
    $xml .= w_para('Le test du Khi2 verifie l\'independance entre deux variables qualitatives. La plateforme affiche :');
    $xml .= w_bullet('Tableau des effectifs observes');
    $xml .= w_bullet('Tableau des effectifs attendus (sous hypothese d\'independance)');
    $xml .= w_bullet('Statistique du Khi2 : chi2 = Somme (O - E)^2 / E');
    $xml .= w_bullet('Degres de liberte et p-value');
    $xml .= w_bullet('V de Cramer (mesure d\'association)');
    $xml .= w_bullet('Interpretation automatique');
    $xml .= w_h3('t de Student et ANOVA');
    $xml .= w_para('Ces tests comparent les moyennes d\'une variable numerique entre groupes :');
    $xml .= w_bullet('Comparaison de deux groupes (statistique t, p-value)', ['bold_prefix' => 't de Student : ']);
    $xml .= w_bullet('Comparaison de trois groupes ou plus (statistique F, p-value)', ['bold_prefix' => 'ANOVA : ']);
    $xml .= w_info_box('Seuil de significativite : ', 'Un p-value inferieur a 0.05 indique une difference ou association statistiquement significative au seuil de 5%.');

    // Section 13
    $xml .= w_h1('13. Correlations de Pearson et Spearman');
    $xml .= w_para('Le module de correlation mesure la relation entre deux variables numeriques :');
    $xml .= w_bullet('Relation lineaire (coefficient r entre -1 et +1)', ['bold_prefix' => 'Correlation de Pearson : ']);
    $xml .= w_bullet('Relation monotone basee sur les rangs', ['bold_prefix' => 'Correlation de Spearman : ']);
    $xml .= w_bullet('Nuage de points (scatter plot) avec Chart.js');
    $xml .= w_bullet('p-value pour tester la significativite');
    $xml .= w_bullet('Interpretation automatique (force et direction de la correlation)');

    // Section 14
    $xml .= w_h1('14. Analyse en Composantes Principales (ACP)');
    $xml .= w_para('L\'ACP reduit la dimensionnalite d\'un ensemble de variables numeriques en identifiant les axes factoriels qui capturent le maximum de variance.');
    $xml .= w_h3('Affichages');
    $xml .= w_bullet('Valeurs propres et pourcentage de variance expliquee');
    $xml .= w_bullet('Eboulis des valeurs propres (scree plot)');
    $xml .= w_bullet('Cercle des correlations sur le premier plan factoriel');
    $xml .= w_bullet('Plan factoriel : projection des individus');
    $xml .= w_bullet('Interpretation automatique des axes');

    // Section 15
    $xml .= w_h1('15. Classification (K-means et CAH)');
    $xml .= w_para('La classification non supervisee regroupe les repondants en clusters homogenes.');
    $xml .= w_h3('K-means');
    $xml .= w_bullet('Selection du nombre de clusters (k)');
    $xml .= w_bullet('Visualisation des clusters sur le plan factoriel');
    $xml .= w_bullet('Profils des clusters (moyennes par variable)');
    $xml .= w_h3('CAH (Classification Ascendante Hierarchique)');
    $xml .= w_bullet('Dendrogramme visuel');
    $xml .= w_bullet('Determination du nombre optimal de classes');
    $xml .= w_bullet('Profils detailles de chaque classe');
    $xml .= w_para('Les deux methodes incluent une interpretation automatique decrivant les caracteristiques de chaque cluster/classe.');

    // Section 16
    $xml .= w_h1('16. Calcul de la taille d\'echantillon');
    $xml .= w_para('Le module d\'echantillonnage permet de determiner la taille optimale de l\'echantillon avant de lancer l\'etude.');
    $xml .= w_table(
        ['Parametre', 'Description', 'Valeur typique'],
        [
            ['Taille de la population', 'Nombre total d\'individus', '10 000'],
            ['Marge d\'erreur', 'Precision souhaitee', '5%'],
            ['Niveau de confiance', '90%, 95% ou 99%', '95%'],
            ['Proportion estimee', 'Utiliser 50% pour une estimation conservatrice', '50%'],
        ]
    );
    $xml .= w_bold_para('Formule utilisee : ', 'n = (Z^2 x p x (1-p)) / e^2 avec correction pour population finie.');

    // Section 17
    $xml .= w_h1('17. Generation du rapport et export PDF');
    $xml .= w_para('Le module Rapports genere un document complet avec tous les resultats de l\'etude.');
    $xml .= w_h3('Contenu du rapport');
    $xml .= w_bullet('Synthese : statut, dates, nombre de repondants, parametres d\'echantillonnage');
    $xml .= w_bullet('Resultats par question : tableaux d\'effectifs, pourcentages, graphiques en barres');
    $xml .= w_bullet('Statistiques descriptives pour les variables numeriques');
    $xml .= w_bullet('Graphiques Chart.js integres pour chaque question');
    $xml .= w_bullet('Reponses ouvertes affichees in extenso');
    $xml .= w_h3('Export PDF');
    $xml .= w_bullet('Accedez a Rapports > Generer et selectionnez l\'etude.', ['bold_prefix' => 'Etape 1 : ']);
    $xml .= w_bullet('Cliquez sur "Imprimer / PDF". Le navigateur ouvre la boite de dialogue d\'impression.', ['bold_prefix' => 'Etape 2 : ']);
    $xml .= w_bullet('Selectionnez la destination "Enregistrer au format PDF" pour generer le fichier.', ['bold_prefix' => 'Etape 3 : ']);

    // Section 18
    $xml .= w_h1('18. Gestion des utilisateurs (administrateur)');
    $xml .= w_para('Reserve aux administrateurs, ce module permet de gerer tous les comptes utilisateurs de la plateforme.');
    $xml .= w_bullet('Liste de tous les utilisateurs avec role, statut et date d\'inscription');
    $xml .= w_bullet('Activation / desactivation des comptes');
    $xml .= w_bullet('Creation de nouveaux comptes (y compris administrateurs)');
    $xml .= w_bullet('Modification du role d\'un utilisateur');
    $xml .= w_bullet('Suppression de comptes');
    $xml .= w_info_box('Attention : ', 'La desactivation d\'un compte empeche la connexion mais conserve les donnees. La suppression est irreversible.', 'FFFBEB', 'FDE68A');

    // Section 19
    $xml .= w_h1('19. Interpretation automatique des resultats');
    $xml .= w_para('Chaque analyse statistique sur la plateforme est accompagnee d\'une interpretation textuelle automatique qui explique les resultats en langage naturel.');
    $xml .= w_h3('Exemples d\'interpretations');
    $xml .= w_bullet('"La modalite \'Tres satisfait\' represente 45% des reponses, ce qui indique une tendance positive."', ['bold_prefix' => 'Tris a plat : ']);
    $xml .= w_bullet('"Le test du Khi2 (chi2=12.34, ddl=4, p=0.015) indique une association significative entre les deux variables au seuil de 5%."', ['bold_prefix' => 'Khi2 : ']);
    $xml .= w_bullet('"L\'ANOVA (F=8.72, p=0.003) revele une difference significative entre les groupes."', ['bold_prefix' => 'ANOVA : ']);
    $xml .= w_bullet('"Correlation positive forte (r=0.78, p<0.001) : les deux variables augmentent conjointement."', ['bold_prefix' => 'Correlation : ']);
    $xml .= w_bullet('"Le premier axe factoriel explique 42% de la variance et oppose les individus selon..."', ['bold_prefix' => 'ACP : ']);

    // Section 20
    $xml .= w_h1('20. FAQ et depannage');
    $xml .= w_h3('Je ne peux pas me connecter');
    $xml .= w_para('Verifiez votre adresse email et mot de passe. Si le probleme persiste, contactez l\'administrateur pour reinitialiser votre compte.');
    $xml .= w_h3('L\'etude n\'est pas accessible aux repondants');
    $xml .= w_para('Verifiez que le statut de l\'etude est "Active". Une etude en "Brouillon" ou "Terminee" n\'est pas accessible.');
    $xml .= w_h3('Aucune donnee n\'apparait dans les analyses');
    $xml .= w_para('Assurez-vous qu\'il y a des repondants ayant termine l\'enquete (statut "termine"). Vous pouvez verifier dans la page de detail de l\'etude.');
    $xml .= w_h3('Le rapport PDF ne contient pas les graphiques');
    $xml .= w_para('Utilisez Chrome ou Edge et cochez "Graphiques d\'arriere-plan" dans les parametres d\'impression. Les graphiques Chart.js necessitent un navigateur moderne.');
    $xml .= w_h3('Je ne peux pas m\'inscrire comme administrateur');
    $xml .= w_para('C\'est normal. L\'inscription en tant qu\'administrateur est desactivee pour des raisons de securite. Contactez un administrateur existant pour creer votre compte.');
    $xml .= w_h3('Les sauts conditionnels ne fonctionnent pas');
    $xml .= w_para('Les sauts conditionnels ne sont disponibles que pour les questions a choix unique (fermee_une). Verifiez que la regle a bien ete configuree dans le constructeur.');

    return $xml;
}

// ===== Documentation Technique Content =====

function build_doc_tech_content() {
    $xml = '';
    $xml .= w_cover_page('Documentation Technique', 'Modele de donnees, architecture et conventions de developpement', 'N 69 - MarketStudy Pro', 'Version 1.0 - ' . date('Y'));
    $xml .= w_toc([
        'Presentation technique du projet',
        'Stack technologique',
        'Architecture de l\'application',
        'Structure des fichiers',
        'Configuration et constantes',
        'Modele de donnees (schema SQL)',
        'Relations entre tables (MCD)',
        'Systeme d\'authentification et RBAC',
        'Fonctions statistiques (stats.php)',
        'Convention de nommage',
        'Conventions de codage',
        'Securite et bonnes pratiques',
        'API et endpoints',
        'Frontend et design system',
        'Installation et deploiement',
    ]);

    // Section 1
    $xml .= w_h1('1. Presentation technique du projet');
    $xml .= w_para('MarketStudy Pro est une application web PHP/MySQL developpee pour le projet N 69 du Master CCA. Elle couvre l\'ensemble du cycle d\'une etude de marche : creation d\'etudes, construction de questionnaires, distribution, collecte de reponses, analyses statistiques avancees et generation de rapports.');
    $xml .= w_para('L\'application est concue pour fonctionner avec XAMPP (Apache + MySQL + PHP) sans framework externe. Toute la logique est en PHP procedural oriente fonctions, avec PDO pour l\'acces a la base de donnees.');
    $xml .= w_h3('Objectifs techniques');
    $xml .= w_bullet('Application autonome sans dependance Composer');
    $xml .= w_bullet('Compatible PHP 7.4+ et PHP 8+');
    $xml .= w_bullet('Requetes preparees systematiques (PDO)');
    $xml .= w_bullet('Design responsive avec CSS moderne (grid, flexbox, variables)');
    $xml .= w_bullet('Graphiques interactifs via Chart.js');
    $xml .= w_bullet('Interpretation statistique automatique en francais');

    // Section 2
    $xml .= w_h1('2. Stack technologique');
    $xml .= w_table(
        ['Composant', 'Technologie', 'Version'],
        [
            ['Langage serveur', 'PHP', '7.4+ / 8+'],
            ['Base de donnees', 'MySQL (MariaDB)', '10.4+ (XAMPP)'],
            ['Acces donnees', 'PDO (PHP Data Objects)', 'Natif PHP'],
            ['Serveur web', 'Apache', '2.4+ (XAMPP)'],
            ['Frontend', 'HTML5 + CSS3 + JavaScript', 'Vanilla (sans framework)'],
            ['Graphiques', 'Chart.js', '4.4.0 (CDN)'],
            ['Icones', 'Font Awesome', '6.5.1 (CDN)'],
            ['Police', 'Google Fonts - Inter', '300-900'],
            ['Securite', 'password_hash (bcrypt)', 'Natif PHP'],
            ['QR Code', 'api.qrserver.com', 'API externe'],
        ]
    );
    $xml .= w_info_box('Aucune dependance Composer : ', 'Toutes les bibliotheques externes (Chart.js, Font Awesome) sont chargees via CDN. L\'application fonctionne immediatement apres copie des fichiers dans htdocs.');

    // Section 3
    $xml .= w_h1('3. Architecture de l\'application');
    $xml .= w_para('L\'application suit une architecture MVC simplifie sans routeur : chaque page est un fichier PHP qui inclut les configurations, traite les donnees, et affiche la vue.');
    $xml .= w_h3('Flux d\'une requete');
    $xml .= w_para('1. Inclusion de la configuration (config.php, stats.php)');
    $xml .= w_para('2. Verification de l\'authentification (isLoggedIn, requireRole)');
    $xml .= w_para('3. Traitement des donnees (POST/GET avec requetes preparees PDO)');
    $xml .= w_para('4. Inclusion du layout (header.php, footer.php) et affichage HTML');
    $xml .= w_h3('Couches de l\'application');
    $xml .= w_table(
        ['Couche', 'Role', 'Fichiers'],
        [
            ['Configuration', 'Constantes, connexion DB, auth, helpers', 'config/config.php'],
            ['Statistiques', 'Fonctions de calcul statistique', 'config/stats.php'],
            ['Layout', 'Header, sidebar, footer', 'includes/header.php, footer.php'],
            ['Vues', 'Pages HTML avec logique PHP', '*.php (racine et sous-dossiers)'],
            ['Assets', 'CSS, JavaScript', 'assets/css/style.css, assets/js/main.js'],
            ['Schema', 'Definition de la base de donnees', 'database/schema.sql'],
        ]
    );

    // Section 4
    $xml .= w_h1('4. Structure des fichiers');
    $xml .= w_para('projet69/');
    $xml .= w_bullet('config/ - config.php (Configuration, helpers, auth), stats.php (Fonctions statistiques)');
    $xml .= w_bullet('includes/ - header.php (Layout), footer.php');
    $xml .= w_bullet('assets/ - css/style.css (Design system), js/main.js (JavaScript + Chart.js)');
    $xml .= w_bullet('database/ - schema.sql (Schema MySQL)');
    $xml .= w_bullet('auth/ - login.php, register.php, logout.php');
    $xml .= w_bullet('admin/ - users.php (Gestion utilisateurs)');
    $xml .= w_bullet('etudes/ - list.php, create.php, view.php, delete.php');
    $xml .= w_bullet('questionnaire/ - builder.php (Constructeur)');
    $xml .= w_bullet('survey/ - take.php, preview.php, thank_you.php');
    $xml .= w_bullet('distribution/ - index.php (Distribution: lien, QR, email)');
    $xml .= w_bullet('analyses/ - tris_a_plat.php, tris_croises.php, khi2.php, anova.php, correlation.php, acp.php, classification.php');
    $xml .= w_bullet('rapports/ - list.php, generate.php (Rapport + export PDF)');
    $xml .= w_bullet('landing.php (Page d\'accueil), echantillonnage.php, setup.php, seed.php, index.php (Tableau de bord), README.md');

    // Section 5
    $xml .= w_h1('5. Configuration et constantes');
    $xml .= w_para('Le fichier config/config.php definit toutes les constantes globales et fonctions utilitaires.');
    $xml .= w_h3('Constantes');
    $xml .= w_table(
        ['Constante', 'Valeur', 'Description'],
        [
            ['DB_HOST', 'localhost', 'Hote MySQL'],
            ['DB_NAME', 'etude_marche', 'Nom de la base'],
            ['DB_USER', 'root', 'Utilisateur MySQL'],
            ['DB_PASS', '(vide)', 'Mot de passe MySQL'],
            ['APP_URL', 'http://localhost/projet69', 'URL de base'],
            ['APP_NAME', 'MarketStudy Pro', 'Nom de l\'application'],
        ]
    );
    $xml .= w_h3('Fonctions utilitaires principales');
    $xml .= w_table(
        ['Fonction', 'Description'],
        [
            ['getDB()', 'Retourne l\'instance PDO (singleton)'],
            ['e($str)', 'Echappe le HTML (htmlspecialchars)'],
            ['getParam($key, $default)', 'Recupere un parametre GET'],
            ['postParam($key, $default)', 'Recupere un parametre POST'],
            ['redirect($url)', 'Redirection HTTP'],
            ['generateToken()', 'Genere un token unique (32 chars)'],
            ['formatDate($date, $format)', 'Formate une date en francais'],
            ['formatNumber($n)', 'Formate un nombre (separateur francais)'],
            ['typeQuestionLabel($type)', 'Libelle francais d\'un type de question'],
            ['statutLabel($statut)', 'Libelle francais d\'un statut d\'etude'],
        ]
    );

    // Section 6
    $xml .= w_h1('6. Modele de donnees (schema SQL)');
    $xml .= w_para('La base de donnees etude_marche contient 8 tables :');
    $xml .= w_h3('Table users');
    $xml .= w_table(
        ['Champ', 'Type', 'Description'],
        [
            ['id', 'INT AUTO_INCREMENT PK', 'Identifiant unique'],
            ['nom', 'VARCHAR(100)', 'Nom de l\'utilisateur'],
            ['prenom', 'VARCHAR(100)', 'Prenom'],
            ['email', 'VARCHAR(255) UNIQUE', 'Email (identifiant de connexion)'],
            ['mot_de_passe', 'VARCHAR(255)', 'Hash bcrypt (password_hash)'],
            ['role', "ENUM('admin','chercheur','repondant')", 'Role de l\'utilisateur'],
            ['telephone', 'VARCHAR(20) NULL', 'Telephone'],
            ['organisation', 'VARCHAR(200) NULL', 'Organisation'],
            ['actif', 'TINYINT(1) DEFAULT 1', 'Compte actif/desactive'],
            ['date_inscription', 'DATETIME', 'Date de creation'],
            ['derniere_connexion', 'DATETIME NULL', 'Derniere connexion'],
        ]
    );
    $xml .= w_h3('Table etudes');
    $xml .= w_table(
        ['Champ', 'Type', 'Description'],
        [
            ['id', 'INT AUTO_INCREMENT PK', 'Identifiant'],
            ['titre', 'VARCHAR(255)', 'Titre de l\'etude'],
            ['description', 'TEXT', 'Description'],
            ['domaine', 'VARCHAR(100)', 'Domaine'],
            ['statut', "ENUM('brouillon','active','terminee')", 'Statut'],
            ['taille_cible', 'INT DEFAULT 100', 'Taille d\'echantillon visee'],
            ['marge_erreur', 'DECIMAL(5,2) DEFAULT 5.00', 'Marge d\'erreur (%)'],
            ['niveau_confiance', 'DECIMAL(5,2) DEFAULT 95.00', 'Confiance (%)'],
            ['methode_echantillonnage', 'ENUM', 'Methode'],
            ['user_id', 'INT NULL FK->users(id)', 'Chercheur createur'],
        ]
    );
    $xml .= w_h3('Table questions');
    $xml .= w_table(
        ['Champ', 'Type', 'Description'],
        [
            ['id', 'INT PK', 'Identifiant'],
            ['section_id', 'INT FK->sections(id)', 'Section parente'],
            ['etude_id', 'INT FK->etudes(id)', 'Etude (redondant pour requetes)'],
            ['libelle', 'TEXT', 'Libelle de la question'],
            ['type', 'ENUM(7 types)', 'Type de question'],
            ['obligatoire', 'TINYINT(1) DEFAULT 1', 'Reponse obligatoire'],
            ['saut_conditionnel', 'TEXT NULL (JSON)', 'Regle de saut conditionnel'],
            ['echelle_min', 'INT DEFAULT 1', 'Valeur min (echelle)'],
            ['echelle_max', 'INT DEFAULT 5', 'Valeur max (echelle)'],
        ]
    );
    $xml .= w_para('Les tables reponses_possibles stockent les options de reponse pour les questions fermees. respondents enregistre chaque participant avec un token unique. reponses contient les reponses individuelles avec support multi-format (ID, texte, numerique, JSON multiple, JSON classement). invitations suit les envois d\'emails. rapports stocke les rapports generes.');

    // Section 7
    $xml .= w_h1('7. Relations entre tables (MCD)');
    $xml .= w_bullet('users(1) -> (N)etudes : un utilisateur cree plusieurs etudes', ['bold_prefix' => '']);
    $xml .= w_bullet('etudes(1) -> (N)sections : une etude contient plusieurs sections');
    $xml .= w_bullet('sections(1) -> (N)questions : une section contient plusieurs questions');
    $xml .= w_bullet('questions(1) -> (N)reponses_possibles : une question a plusieurs options');
    $xml .= w_bullet('etudes(1) -> (N)respondents : une etude a plusieurs repondants');
    $xml .= w_bullet('respondents(1) -> (N)reponses : un repondant donne plusieurs reponses');
    $xml .= w_bullet('questions(1) -> (N)reponses : une question recoit plusieurs reponses');
    $xml .= w_h3('Regles de suppression (ON DELETE)');
    $xml .= w_bullet('CASCADE : sections->etudes, questions->sections, reponses->respondents, reponses->questions');
    $xml .= w_bullet('SET NULL : etudes.user_id->users, reponses.reponse_possibles_id->reponses_possibles');

    // Section 8
    $xml .= w_h1('8. Systeme d\'authentification et RBAC');
    $xml .= w_para('L\'authentification utilise les sessions PHP avec password_hash() (bcrypt) pour le stockage des mots de passe.');
    $xml .= w_table(
        ['Fonction', 'Description'],
        [
            ['startSession()', 'Demarre la session PHP'],
            ['isLoggedIn()', 'Verifie si l\'utilisateur est connecte'],
            ['currentUser()', 'Retourne les donnees de l\'utilisateur connecte'],
            ['currentRole()', 'Retourne le role de l\'utilisateur'],
            ['hasRole($roles)', 'Verifie si l\'utilisateur a l\'un des roles'],
            ['requireLogin()', 'Redirige vers login si non connecte'],
            ['requireRole($roles)', 'Redirige si le role n\'est pas autorise'],
            ['loginUser($email, $password)', 'Authentifie et cree la session'],
            ['logoutUser()', 'Detruit la session'],
            ['registerUser($data)', 'Cree un compte (empeche l\'auto-admin)'],
        ]
    );
    $xml .= w_info_box('Securite : ', 'La fonction registerUser() force le role a "repondant" si "admin" est soumis. L\'inscription admin n\'est possible que via le panneau d\'administration.', 'FFFBEB', 'FDE68A');

    // Section 9
    $xml .= w_h1('9. Fonctions statistiques (stats.php)');
    $xml .= w_para('Le fichier config/stats.php contient toutes les fonctions de calcul statistique implementees en PHP pur (sans extension externe).');
    $xml .= w_table(
        ['Fonction', 'Description'],
        [
            ['moyenne($values)', 'Moyenne arithmetique'],
            ['mediane($values)', 'Mediane'],
            ['ecartType($values)', 'Ecart-type (echantillon)'],
            ['khi2($observes, $attendus)', 'Statistique du Khi2'],
            ['cramerV($khi2, $n, $dl)', 'V de Cramer'],
            ['tStudent($g1, $g2)', 'Test t de Student'],
            ['anova($groupes)', 'Analyse de variance (F-statistic)'],
            ['pearson($x, $y)', 'Correlation de Pearson'],
            ['spearman($x, $y)', 'Correlation de Spearman'],
            ['acp($data)', 'Analyse en Composantes Principales'],
            ['kmeans($data, $k)', 'Classification K-means'],
            ['cah($data)', 'Classification Ascendante Hierarchique'],
            ['calculerTailleEchantillon($N, $e, $conf)', 'Taille d\'echantillon optimale'],
        ]
    );

    // Section 10
    $xml .= w_h1('10. Convention de nommage');
    $xml .= w_table(
        ['Element', 'Convention', 'Exemple'],
        [
            ['Tables', 'snake_case, pluriel francais', 'etudes, questions, reponses'],
            ['Colonnes', 'snake_case', 'user_id, date_creation'],
            ['Cles primaires', 'id', 'id'],
            ['Cles etrangeres', 'table_singular_id', 'etude_id, section_id'],
            ['Fichiers PHP', 'snake_case', 'tris_a_plat.php'],
            ['Fonctions PHP', 'camelCase', 'getDB(), isLoggedIn()'],
            ['Variables PHP', 'camelCase', '$etude_id, $current_user'],
            ['Constantes', 'UPPER_SNAKE', 'APP_URL, DB_HOST'],
            ['CSS classes', 'kebab-case', '.card-header, .btn-primary'],
            ['CSS variables', '--kebab-case', '--primary, --gray-900'],
        ]
    );

    // Section 11
    $xml .= w_h1('11. Conventions de codage');
    $xml .= w_bullet('UTF-8 pour tous les fichiers (charset utf8mb4 pour MySQL)');
    $xml .= w_bullet('Requetes preparees systematiques (PDO + execute avec placeholders ?)');
    $xml .= w_bullet('htmlspecialchars() via e() pour tout affichage de donnees utilisateur');
    $xml .= w_bullet('Validation cote serveur pour tous les formulaires');
    $xml .= w_bullet('Redirection apres POST (PRG pattern) pour eviter les doubles soumissions');
    $xml .= w_bullet('Commentaires en francais pour la logique metier');
    $xml .= w_bullet('Indentation : 4 espaces (pas de tabulations)');
    $xml .= w_bullet('Accolades obligatoires meme pour les blocs d\'une ligne');

    // Section 12
    $xml .= w_h1('12. Securite et bonnes pratiques');
    $xml .= w_table(
        ['Menace', 'Protection'],
        [
            ['Injection SQL', 'Requetes preparees PDO (placeholders ?)'],
            ['XSS', 'htmlspecialchars() via e() sur tout output'],
            ['Mots de passe en clair', 'password_hash() bcrypt + password_verify()'],
            ['Escalade de privileges', 'requireRole() sur chaque page protegee'],
            ['Auto-inscription admin', 'registerUser() force "repondant" si "admin" soumis'],
            ['Session fixation', 'session_regenerate_id() a la connexion'],
            ['Acces sans auth', 'requireLogin() + redirection automatique'],
        ]
    );

    // Section 13
    $xml .= w_h1('13. API et endpoints');
    $xml .= w_para('L\'application n\'utilise pas d\'API REST. Chaque page est un endpoint qui accepte GET et/ou POST.');
    $xml .= w_h3('Endpoints publics');
    $xml .= w_table(
        ['URL', 'Methode', 'Description'],
        [
            ['/landing.php', 'GET', 'Page d\'accueil publique'],
            ['/auth/login.php', 'GET/POST', 'Connexion'],
            ['/auth/register.php', 'GET/POST', 'Inscription'],
            ['/auth/logout.php', 'GET', 'Deconnexion'],
            ['/survey/take.php?etude_id=N&token=XXX', 'GET/POST', 'Participation au sondage'],
        ]
    );
    $xml .= w_h3('Endpoints authentifies');
    $xml .= w_table(
        ['URL', 'Roles', 'Description'],
        [
            ['/index.php', 'Tous', 'Tableau de bord'],
            ['/etudes/list.php', 'admin, chercheur', 'Liste des etudes'],
            ['/etudes/create.php', 'admin, chercheur', 'Creation d\'etude'],
            ['/questionnaire/builder.php', 'admin, chercheur', 'Constructeur'],
            ['/distribution/index.php', 'admin, chercheur', 'Distribution'],
            ['/analyses/*.php', 'admin, chercheur', 'Toutes les analyses'],
            ['/echantillonnage.php', 'admin, chercheur', 'Calcul d\'echantillon'],
            ['/rapports/generate.php', 'admin, chercheur', 'Generation rapport'],
            ['/admin/users.php', 'admin', 'Gestion utilisateurs'],
        ]
    );

    // Section 14
    $xml .= w_h1('14. Frontend et design system');
    $xml .= w_para('Le design system est defini dans assets/css/style.css avec des variables CSS.');
    $xml .= w_h3('Variables CSS principales');
    $xml .= w_bullet('--primary: #4f46e5 (Indigo)');
    $xml .= w_bullet('--primary-light: #818cf8');
    $xml .= w_bullet('--accent: #ec4899 (Rose)');
    $xml .= w_bullet('--success: #10b981 (Vert)');
    $xml .= w_bullet('--warning: #f59e0b (Ambre)');
    $xml .= w_bullet('--danger: #ef4444 (Rouge)');
    $xml .= w_bullet('--gray-900 a --gray-50: Palette de gris');
    $xml .= w_h3('Composants CSS');
    $xml .= w_table(
        ['Composant', 'Classes'],
        [
            ['Boutons', '.btn .btn-primary / .btn-outline / .btn-danger / .btn-sm / .btn-lg'],
            ['Cartes', '.card .card-header .card-body'],
            ['Formulaires', '.form-control .form-label .form-group .form-row'],
            ['Tableaux', '.table'],
            ['Badges', '.badge .badge-primary / .badge-secondary'],
            ['Alertes', '.alert .alert-success / .alert-danger / .alert-info'],
            ['Grille', '.grid .grid-2 / .grid-3 / .grid-4'],
            ['Modal', '.modal-overlay .modal .modal-header / .modal-body / .modal-footer'],
            ['Interpretation', '.interpretation .interp-label'],
        ]
    );

    // Section 15
    $xml .= w_h1('15. Installation et deploiement');
    $xml .= w_bullet('Copier le dossier projet69/ dans htdocs/ de XAMPP.', ['bold_prefix' => 'Etape 1 : ']);
    $xml .= w_bullet('Lancer Apache et MySQL depuis le panneau XAMPP.', ['bold_prefix' => 'Etape 2 : ']);
    $xml .= w_bullet('Ouvrir http://localhost/projet69/setup.php - cree la BDD, les tables et les comptes par defaut.', ['bold_prefix' => 'Etape 3 : ']);
    $xml .= w_bullet('Ouvrir http://localhost/projet69/seed.php - cree une etude exemple avec 30 repondants.', ['bold_prefix' => 'Etape 4 : ']);
    $xml .= w_bullet('Ouvrir http://localhost/projet69/landing.php', ['bold_prefix' => 'Etape 5 : ']);
    $xml .= w_info_box('Production : ', 'Pour un deploiement en production, modifier DB_USER, DB_PASS, APP_URL dans config.php, et desactiver l\'affichage des erreurs PHP.');

    return $xml;
}

// ===== Generate both documents =====

$manuel_path = __DIR__ . '/docs/Manuel_Utilisateur_MarketStudy_Pro.docx';
$doc_tech_path = __DIR__ . '/docs/Documentation_Technique_MarketStudy_Pro.docx';

build_docx(build_manuel_content(), $manuel_path);
build_docx(build_doc_tech_content(), $doc_tech_path);

$gen_errors = [];
if (!file_exists($manuel_path)) $gen_errors[] = 'Manuel utilisateur: echec';
if (!file_exists($doc_tech_path)) $gen_errors[] = 'Documentation technique: echec';
$generated = empty($gen_errors);
$current_user = currentUser();
$current_role = currentRole();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generation des documents Word — MarketStudy Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><i class="fas fa-chart-pie" style="color:white;"></i></div>
            <div class="brand-text">MarketStudy Pro<small>Etudes de marche</small></div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">Principal</div>
                <a href="<?= APP_URL ?>/index.php" class="sidebar-link"><i class="fas fa-gauge-high"></i> Tableau de bord</a>
                <a href="<?= APP_URL ?>/etudes/list.php" class="sidebar-link"><i class="fas fa-folder-open"></i> Mes etudes</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Documentation</div>
                <a href="<?= APP_URL ?>/generate_docs.php" class="sidebar-link active"><i class="fas fa-file-word"></i> Generer les docs Word</a>
            </div>
        </nav>
    </aside>
    <div class="main-content">
        <header class="top-header">
            <h1 class="page-title">Generation des documents Word</h1>
            <div class="header-actions">
                <div style="display: flex; align-items: center; gap: 8px; padding-left: 16px; border-left: 1px solid var(--gray-200);">
                    <div style="text-align: right;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--gray-700);"><?= e($current_user['prenom'] . ' ' . $current_user['nom']) ?></div>
                        <div style="font-size: 11px; color: var(--gray-400);"><?= roleLabel($current_role) ?></div>
                    </div>
                    <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-outline btn-sm" title="Deconnexion"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </header>
        <main class="content-area">
            <div style="max-width: 700px; margin: 40px auto;">
                <?php if ($generated): ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 48px 32px;">
                        <div style="width: 72px; height: 72px; background: linear-gradient(135deg, #10b981, #34d399); border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(16,185,129,0.3);">
                            <i class="fas fa-check" style="color: white; font-size: 32px;"></i>
                        </div>
                        <h2 style="font-size: 24px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px;">Documents generes avec succes</h2>
                        <p style="color: var(--gray-500); margin-bottom: 32px;">Les deux documents Word (.docx) ont ete crees dans le dossier <code>docs/</code></p>
                        <div style="display: flex; flex-direction: column; gap: 16px; max-width: 480px; margin: 0 auto;">
                            <a href="<?= APP_URL ?>/docs/Manuel_Utilisateur_MarketStudy_Pro.docx" class="btn btn-primary" style="padding: 16px 24px; text-decoration: none;">
                                <i class="fas fa-download"></i> Telecharger le Manuel Utilisateur (.docx)
                            </a>
                            <a href="<?= APP_URL ?>/docs/Documentation_Technique_MarketStudy_Pro.docx" class="btn btn-primary" style="padding: 16px 24px; text-decoration: none;">
                                <i class="fas fa-download"></i> Telecharger la Documentation Technique (.docx)
                            </a>
                        </div>
                        <div style="margin-top: 32px; padding: 16px; background: var(--gray-50); border-radius: 10px; font-size: 13px; color: var(--gray-500);">
                            <p><i class="fas fa-info-circle"></i> Les fichiers sont compatibles Microsoft Word, LibreOffice Writer et Google Docs.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 48px 32px;">
                        <div style="width: 72px; height: 72px; background: linear-gradient(135deg, #ef4444, #f87171); border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                            <i class="fas fa-exclamation" style="color: white; font-size: 32px;"></i>
                        </div>
                        <h2 style="font-size: 24px; font-weight: 800; color: var(--gray-900); margin-bottom: 8px;">Erreur de generation</h2>
                        <p style="color: var(--gray-500); margin-bottom: 16px;">Impossible de generer les documents.</p>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto; color: var(--danger); font-size: 14px;">
                            <?php foreach ($gen_errors as $err): ?>
                            <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p style="color: var(--gray-400); margin-top: 16px; font-size: 13px;">Verifiez que le dossier <code>docs/</code> est accessible en ecriture.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>
