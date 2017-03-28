<?php

global $project;
$project = 'mysite';

global $databaseConfig;
/* * ********** Start Enviroment Config *************** */
if (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || ($_SERVER['REMOTE_ADDR'] == '<MyIP>' && strpos($_SERVER['HTTP_USER_AGENT'], '+') !== false))) {
    $databaseConfig = array(
        "type" => 'MySQLDatabase',
        "server" => 'localhost',
        "username" => 'root',
        "password" => '',
        "database" => 'ss-shop',
        "path" => '',
    );
    Security::setDefaultAdmin('admin', '123');
} else {
    $databaseConfig['type'] = 'MySQLDatabase';
    $databaseConfig['server'] = 'localhost';
    $databaseConfig["username"] = '';
    $databaseConfig["password"] = '';
    $databaseConfig["database"] = '';
    $databaseConfig["path"] = '';
    Security::setDefaultAdmin('admin', 'admin');
}
/* * ********** End Enviroment Config *************** */
//Email::set_mailer(new SmtpMailer());
Director::set_environment_type("dev");
MySQLDatabase::set_connection_charset('utf8');
SSViewer::set_theme('simple');
// enable nested URLs for this site (e.g. page/sub-page/)
SiteTree::enable_nested_urls();
HtmlEditorConfig::get('cms')->insertButtonsAfter('tablecontrols', 'fontsizeselect', 'forecolor', 'backcolor');
FulltextSearchable::enable(array('SiteTree'));
SiteConfig::add_extension('CustomSiteConfig');
//DataObject::add_extension('DataObjectExtension');

if (Director::isDev()) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} 
