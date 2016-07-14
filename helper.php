<?php
defined('_JEXEC') or die;
require_once JPATH_SITE . '/components/com_content/helpers/route.php';
class ModCat
{
    public static function getList($params)
    {
        $db = JFactory::getDbo();
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $categoria = $params->get('catid');
        $categoria = $params->get('catidb');
        return $categoria;
    }
}