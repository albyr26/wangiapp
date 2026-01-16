<?php
// clear_edit_session.php
session_start();

if (isset($_GET["id"]) && isset($_SESSION["last_updated_product"])) {
    if ($_SESSION["last_updated_product"]["id"] == $_GET["id"]) {
        unset($_SESSION["last_updated_product"]);
    }
}

if (isset($_SESSION["success_popup"])) {
    unset($_SESSION["success_popup"]);
}

echo "OK";
