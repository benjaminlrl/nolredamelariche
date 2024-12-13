<?php 
function getIpAddresses() {
    $ipv4 = null;
    $ipv6 = null;

    $output = [];
    exec("ip -o -4 addr show", $output);
    foreach ($output as $line) {
        if (preg_match('/inet (\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
            $ipv4 = $matches[1];
            break;
        }
    }

    $output = [];
    exec("ip -o -6 addr show", $output);
    foreach ($output as $line) {
        if (preg_match('/inet6 ([0-9a-f:]+)/', $line, $matches)) {
            $ipv6 = $matches[1];
            break;
        }
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
    $os = PHP_OS_FAMILY; // Détecte la famille du système d'exploitation
    
    if ($os === 'Windows') {
        // Commande pour récupérer l'adresse MAC sous Windows
        $output = shell_exec('getmac');
        if ($output) {
            preg_match('/([a-fA-F0-9]{2}[:-]){5}[a-fA-F0-9]{2}/', $output, $matches);
            return $matches[0] ?? 'Non disponible';
        }

        // Alternative avec WMIC
        $output = shell_exec("wmic nic where (NetEnabled=TRUE) get MACAddress");
        if ($output) {
            preg_match('/([a-fA-F0-9]{2}[:-]){5}[a-fA-F0-9]{2}/', $output, $matches);
            return $matches[0] ?? 'Non disponible';
        }
    } elseif ($os === 'Linux' || $os === 'Darwin') {
        // Commande pour récupérer l'adresse MAC sous Linux ou macOS
        $output = shell_exec('ip link show');
        if ($output) {
            preg_match('/([a-fA-F0-9]{2}[:-]){5}[a-fA-F0-9]{2}/', $output, $matches);
            return $matches[0] ?? 'Non disponible';
        }

        // Alternative avec ifconfig (au cas où)
        $output = shell_exec('ifconfig -a');
        if ($output) {
            preg_match('/([a-fA-F0-9]{2}[:-]){5}[a-fA-F0-9]{2}/', $output, $matches);
            return $matches[0] ?? 'Non disponible';
        }
    } else {
        // Si le système d'exploitation n'est pas reconnu
        return 'OS inconnu - Impossible de récupérer l\'adresse MAC';
    }

    // Si aucune méthode n'a fonctionné
    return 'Non disponible';
}

?>