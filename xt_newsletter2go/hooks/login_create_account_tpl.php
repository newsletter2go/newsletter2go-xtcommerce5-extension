<?php
/*
#########################################################################
#                       xt:Commerce VEYTON 4.0 Shopsoftware
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
#
# Copyright 2007-2011 xt:Commerce International Ltd. All Rights Reserved.
# This file may not be redistributed in whole or significant part.
# Content of this file is Protected By International Copyright Laws.
#
# ~~~~~~ xt:Commerce VEYTON 4.0 Shopsoftware IS NOT FREE SOFTWARE ~~~~~~~
#
# http://www.xt-commerce.com
#
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
#
# @version $Id: login_create_account_tpl.php 4611 2011-03-30 16:39:15Z tu $
# @copyright xt:Commerce International Ltd., www.xt-commerce.com
#
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
#
# xt:Commerce International Ltd., Kafkasou 9, Aglantzia, CY-2112 Nicosia
#
# office@xt-commerce.com
#
#########################################################################
*/
defined('_VALID_CALL') or die('Direct Access is not allowed.');

if (XT_NEWSLETTER2GO_CHECKBOX == 'true') {
    echo _displayNL2GocheckBox($data_nl);
}

function _displayNL2GocheckBox ($data)
{
    global $xtPlugin, $xtLink, $db;

    $tpl = 'newsletter_checkbox.html';
    $tpl_data = array();
    $template = new Template();
    $template->getTemplatePath($tpl, 'xt_newsletter2go', '', 'plugin');

    $tmp_data = $template->getTemplate('xt_newsletter2go_smarty', $tpl, $tpl_data);
    return $tmp_data;
}
