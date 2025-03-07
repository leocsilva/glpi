<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Inventory\Request;

if (!defined('GLPI_ROOT')) {
    include('../inc/includes.php');
}

$inventory_request = new Request();
$inventory_request->handleHeaders();

$handle = true;
if (isset($_GET['refused'])) {
    $refused = new RefusedEquipment();
    if ($refused->getFromDB($_GET['refused']) && ($inventory_file = $refused->getInventoryFileName()) !== null) {
        $contents = file_get_contents($inventory_file);
    } else {
        trigger_error(
            sprintf('Invalid RefusedEquipment "%s" or inventory file missing', $_GET['refused']),
            E_USER_WARNING
        );
        $contents = '';
    }
} else if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    if (isset($_GET['action']) && $_GET['action'] == 'getConfig') {
        /**
         * Even if Fusion protocol is not supported for getConfig requests, they
         * should be handled and answered with a json content type
         */
        $inventory_request->handleContentType('application/json');
        $inventory_request->addError('Protocol not supported', 400);
    } else {
        // Method not allowed answer without content
        $inventory_request->addError(null, 405);
    }
    $handle = false;
} else {
    $contents = file_get_contents("php://input");
}

if ($handle === true) {
    try {
        $inventory_request->handleRequest($contents);
    } catch (\Exception $e) {
        $inventory_request->addError($e->getMessage());
    }
}

if (isset($_GET['refused'])) {
    $redirect_url = $refused->handleInventoryRequest($inventory_request);
    Html::redirect($redirect_url);
} else {
    $headers = $inventory_request->getHeaders(true);
    http_response_code($inventory_request->getHttpResponseCode());
    foreach ($headers as $key => $value) {
        header(sprintf('%1$s: %2$s', $key, $value));
    }
    echo $inventory_request->getResponse();
}
