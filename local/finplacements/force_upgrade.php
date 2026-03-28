<?php
require_once('../../config.php');

// Define the old version we are upgrading FROM
$oldversion = 2026032600;

// Force run the upgrade logic
require_once(__DIR__ . '/db/upgrade.php');
if (xmldb_local_finplacements_upgrade($oldversion)) {
    
    // Tell Moodle the upgrade is complete so it stops restricting the admin page
    set_config('version', 2026032800, 'local_finplacements');
    
    echo "SUCCESS: Database indexes successfully applied and Moodle version check bypassed!";
} else {
    echo "ERROR: Could not apply indexes.";
}
