<?php function getIpAddresses() {
    $ipv4 = $_SERVER['REMOTE_ADDR'];  // Récupère l'IP IPv4
    $ipv6 = null;  // Valeur par défaut, si IPv6 n'est pas disponible

    // Vérifier si l'IPv6 est présente dans les en-têtes
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipv6 = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return ['ipv4' => $ipv4, 'ipv6' => $ipv6];
}

function getLocationFromIP($ip) {
    // URL de l'API pour obtenir la localisation à partir de l'IP
    $url = "http://ipinfo.io/{$ip}/json";

    // Faire une requête HTTP pour obtenir les données JSON
    $response = file_get_contents($url);
    
    if ($response === FALSE) {
        // Si la requête échoue, renvoyer un message d'erreur
        return "Erreur lors de la récupération de la localisation.";
    }
    
    // Décoder la réponse JSON
    $data = json_decode($response, true);

    // Vérifier si la localisation a été récupérée
    if (isset($data['city']) && isset($data['country'])) {
        return $data['city'] . ', ' . $data['country'];  // Retourner la ville et le pays
    } else {
        return "Localisation non disponible";  // Si les données sont incomplètes
    }
}

function getMacAddress() {
    // Utilisation de la commande système pour obtenir l'adresse MAC
    ob_start();
    // La commande diffère selon le système d'exploitation
    if (stristr(PHP_OS, 'win')) {
        // Si le serveur est sous Windows
        $command = "getmac";
    } else {
        // Si le serveur est sous Linux/Mac
        $command = "ifconfig -a | grep -Po '([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}'";
    }
    // Exécution de la commande
    passthru($command);
    // Capture de la sortie de la commande
    $output = ob_get_clean();
    
    // Retourne la première adresse MAC trouvée
    return $output;
}


?>