<?php


namespace WCMultiShipping\inc\admin\classes\mondial_relay;


use SoapClient;

class mondial_relay_api_helper
{
    const TRACKING_API_KEY = "";

    const API_URL = 'https://api.mondialrelay.com/Web_Services.asmx?wsdl';

    var $error_code = 1;

    var $error_message = '';

    public function get_pickup_point($params)
    {
        if (empty($params)) return false;

        try {
            $client = new SoapClient(self::API_URL);
            $result = $client->WSI4_PointRelais_Recherche($params);

            return $result->WSI4_PointRelais_RechercheResult;
        } catch (Exception $e) {
            return false;
        }
    }

    public function register_parcels($params)
    {
        $shipping_service_url = 'https://api.mondialrelay.com/Web_Services.asmx?wsdl';
        try {
            $client = new SoapClient($shipping_service_url, ['trace' => true]);
            $result = $client->WSI2_CreationEtiquette($params);

            if (!isset($result->WSI2_CreationEtiquetteResult->STAT) || $result->WSI2_CreationEtiquetteResult->STAT !== '0') {
                if (is_admin()) {
                    wms_enqueue_message(
                        sprintf(
                            __('Order %s : Parcel not generated. Please check the logs for more information', 'wc-multishipping'),
                            $params['NDossier']
                        ),
                        'error'
                    );
                }
            }

            return $result->WSI2_CreationEtiquetteResult;
        } catch (SoapFault $fault) {
            wms_logger(__('Webservice error: Soap Connection Issue (shippingMultiParcelWithReservationV3)', 'wc-multishipping'));

            return false;
        } catch (Exception $e) {
            wms_logger(__('Webservice error: Soap Connection Issue (shippingMultiParcelWithReservationV3)', 'wc-multishipping'));

            return false;
        }
    }

    public function get_labels_from_api($label_URL)
    {

        $label_URL = 'https://www.mondialrelay.com'.$label_URL;

        $response = wp_remote_get($label_URL);
        $http_code = wp_remote_retrieve_response_code($response);
        $pdf_body = wp_remote_retrieve_body($response);

        if ('200' != $http_code) {
            wms_logger(sprintf(__("Mondial Relay ERROR - Get Label : %s => %s", 'wc-multishipping'), $http_code, $pdf_body));

            return false;
        }

        return ($pdf_body);
    }

    public function get_error_message($error_code)
    {

        $error_messages = [
            '0' => "Op??ration effectu??e avec succ??s",
            '1' => 'Enseigne invalide',
            '2' => 'Num??ro d\'enseigne vide ou inexistant',
            '3' => 'Num??ro de compte enseigne invalide',
            '8' => 'Mot de passe ou hachage invalide',
            '5' => 'Num??ro de dossier enseigne invalide',
            '7' => 'Num??ro de client enseigne invalide(champ NCLIENT)',
            '9' => 'Ville non reconnu ou non unique',
            '10' => 'Type de collecte invalide',
            '11' => 'Num??ro de Relais de Collecte invalide',
            '12' => 'Pays de Relais de collecte invalide',
            '13' => 'Type de livraison invalide',
            '14' => 'Num??ro de Relais de livraison invalide',
            '15' => 'Pays de Relais de livraison invalide',
            '20' => 'Poids du colis invalide',
            '21' => 'Taille(Longueur + Hauteur) du colis invalide',
            '22' => 'Taille du Colis invalide',
            '24' => 'Num??ro d\'exp??dition ou de suivi invalide',
            '26' => 'Temps de montage invalide',
            '27' => 'Mode de collecte ou de livraison invalide',
            '28' => 'Mode de collecte invalide',
            '29' => 'Mode de livraison invalide. Rappel : 1 Colis max pour l\'offre "Start"',
            '30' => 'Adresse(L1) invalide',
            '31' => 'Adresse(L2) invalide',
            '33' => 'Adresse(L3) invalide',
            '34' => 'Adresse(L4) invalide',
            '35' => 'Ville invalide',
            '36' => 'Code postal invalide',
            '37' => 'Pays invalide',
            '38' => 'Num??ro de t??l??phone invalide',
            '39' => 'Adresse e-mail invalide',
            '40' => 'Param??tres manquants',
            '42' => 'Montant CRT invalide',
            '43' => 'Devise CRT invalide',
            '44' => 'Valeur du colis invalide',
            '45' => 'Devise de la valeur du colis invalide',
            '46' => 'Plage de num??ro d\'exp??dition ??puis??e',
            '47' => 'Nombre de colis invalide',
            '48' => 'Multi - Colis Relais Interdit',
            '49' => 'Action invalide',
            '60' => 'Champ texte libre invalide(Ce code erreur n\'est pas invalidant)',
            '61' => 'Top avisage invalide',
            '62' => 'Instruction de livraison invalide',
            '63' => 'Assurance invalide',
            '64' => 'Temps de montage invalide',
            '65' => 'Top rendez - vous invalide',
            '66' => 'Top reprise invalide',
            '67' => 'Latitude invalide',
            '68' => 'Longitude invalide',
            '69' => 'Code Enseigne invalide',
            '70' => 'Num??ro de Point Relais invalide',
            '71' => 'Nature de point de vente non valide',
            '74' => 'Langue invalide',
            '78' => 'Pays de Collecte invalide',
            '79' => 'Pays de Livraison invalide',
            '80' => 'Code tracing : Colis enregistr??',
            '81' => 'Code tracing : Colis en traitement chez Mondial Relay',
            '82' => 'Code tracing : Colis livr??',
            '83' => 'Code tracing : Anomalie',
            '84' => '(R??serv?? Code Tracing)',
            '85' => '(R??serv?? Code Tracing)',
            '86' => '(R??serv?? Code Tracing)',
            '87' => '(R??serv?? Code Tracing)',
            '88' => '(R??serv?? Code Tracing)',
            '89' => '(R??serv?? Code Tracing)',
            '92' => 'Le code pays du destinataire et le code pays du Point Relais doivent ??tre identiques ou Solde insuffisant(comptes pr??pay??s)',
            '93' => 'Aucun ??l??ment retourn?? par le plan de tri Si vous effectuez une collecte ou une livraison en Point Relais, v??rifiez que les Point Relais sont bien disponibles.Si vous effectuez une livraison ?? domicile, il est probable que le code postal que vous avez indiqu?? n\'existe pas.',
            '94' => 'Colis Inexistant',
            '95' => 'Compte Enseigne non activ??',
            '96' => 'Type d\'enseigne incorrect en Base',
            '97' => 'Cl?? de s??curit?? invalide Cf. : ?? ??G??n??ration de la cl?? de s??curit????',
            '98' => 'Erreur g??n??rique(Param??tres invalides) Cette erreur masque une autre erreur de la liste et ne peut se produire que dans le cas o?? le compte utilis?? est en mode ??Production??.Cf. : ?? ??Fonctionnement normal et d??bogage??',
            '99' => 'Erreur g??n??rique du service. Cette erreur peut ??tre due ?? un probl??me technique du service. Veuillez notifier cette erreur ?? Mondial Relay en pr??cisant la date et l\'heure de la requ??te ainsi que les param??tres envoy??s afin d\'effectuer une v??rification.',
        ];

        if (isset($error_messages[$error_code])) return $error_messages[$error_code];

        return '';
    }


    public function get_status($params)
    {
        if (empty($params)) return false;
        try {
            $client = new SoapClient(self::API_URL);
            $result = $client->WSI2_TracingColisDetaille($params);

            return $result->WSI2_TracingColisDetailleResult;
        } catch (Exception $e) {
            return false;
        }
    }

}
