<?php
defined('_JEXEC') or die;
require_once __DIR__ . '/helper.php';

$document = JFactory::getDocument();
$mod_path = 'modules/mod_geoip/';

//Get parameters from categories selected in admin(You can define what you want you in mod_geoip.xml) 
$list = ModCat::getList($params); //Get parameters 
//Say to Helper to use your Layout
require JModuleHelper::getLayoutPath('mod_geoip');

//Parameters to define filter of category to show in block1 and block2
$catblock1 = $params->get('catblock1');
$catblock2 = $params->get('catblock2');

//setcookie to use the same categories in another queries
setcookie('categoriaselecionada', $catblock1, 60 * 60 * 24 * 360 + time(),'/');
setcookie('categoriaselecionadab', $catblock2, 60 * 60 * 24 * 360 + time(),'/');

// Check if have cookie or not, the cookie will define the filter for change for  geolocation
if(isset($_COOKIE['Pais']))
{
    $regiao = $_COOKIE['Pais'];
    if(isset($_COOKIE['tag']))
    {
        $cattag = $_COOKIE['tag'];
    } else {
        $cattag = "";
    }
}
else
{
    //If is your first time in site it will running this 
    //Find database for IP and geolocation
    //path to geoip
    $GeoPath= $mod_path .'/geoip'.'/';    
    include($GeoPath.'geoip.inc');

    //Define what kind of server you will get
    //Some options you can find https://secure.php.net/manual/en/reserved.variables.server.php
    $ip = $_SERVER['REMOTE_ADDR'];

    //Function to get Geolocation 
    $gi = geoip_open($GeoPath.'GeoIP.dat',GEOIP_STANDARD);
    $regiao = geoip_country_code_by_addr($gi, $ip);
    $regiaopais = geoip_country_name_by_addr($gi, $ip);
    
    //Cookie define when the user enter first time
    setcookie('Pais', $regiao, 60 * 60 * 24 * 360 + time(),'/');
}


ConectAll($regiao,$cattag,$catblock1);
//ConectEvents($regiao,$cattag,$catblock2);

/**
 * Returns the result of a Joomla query to the articles and dpfields tables, In this case we need to got 2 tables
 * @param string $reg region code
 * @param int $cat category id
 * @param int $catsel category select in parameters
 * @return array with query results
 */
function _queryArticles($reg="",$cat="",$catsel)
{

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $arts = array();
    if(empty($reg)&&empty($cat))
    {
        $query->select(array(
            $db->qn('con.id'),
            $db->qn('con.title','art_title'),
            $db->qn('con.alias'),
            $db->qn('con.images'),
            $db->qn('con.introtext'),
            $db->qn('con.catid'),
            $db->qn('con.language')
            )
        );

        $query->from($db->qn('#__content', 'con'));
        $query ->where($db->qn('con.state').' = 1');
        $query ->where($db->qn('con.catid').'='.$db->q($catsel));
        $query ->order($db->qn('con.publish_up') . ' DESC');
        $db->setQuery($query);
        $arts = $db->loadObjectList();
    } else {
        $query->select(array(
            $db->qn('con.id'),
            $db->qn('con.title','art_title'),
            $db->qn('con.alias'),
            $db->qn('con.images'),
            $db->qn('con.introtext'),
            $db->qn('con.catid'),
            $db->qn('con.language'),
             "GROUP_CONCAT(".$db->qn('tag.alias')." SEPARATOR ';') AS tags_alias",
             "GROUP_CONCAT(".$db->qn('tag.title')." SEPARATOR ';') AS tags_title",
            )
        );

        $query->from($db->qn('#__content', 'con'));
        $query->innerJoin($db->qn('#__contentitem_tag_map', 'tm') . ' ON (' . $db->qn('con.id') . ' = ' . $db->qn('tm.content_item_id') . ')');
        $query->innerJoin($db->qn('#__tags', 'tag') . ' ON (' . $db->qn('tag.id') . ' = ' . $db->qn('tm.tag_id') . ')');
        $query ->where($db->qn('con.state').' = 1');
        $query ->where($db->qn('con.catid').'='.$db->q($catsel));
        $query ->group($db->qn('con.id'));
        $query ->order($db->qn('con.publish_up') . ' DESC');

        $db->setQuery($query);
        $arts_wtags = $db->loadObjectList();
        if(!empty($cat)&&!empty($reg)) {
            foreach ($arts_wtags as $k => $a) {
                $tags = explode(';', $a->tags_alias);
                if(in_array(mb_strtolower($cat), $tags)&&in_array(mb_strtolower($reg), $tags))
                    $arts[] = $a;
            }
        } elseif(!empty($cat)) {
            foreach ($arts_wtags as $k => $a) {
                $tags = explode(';', $a->tags_alias);
                if(in_array(mb_strtolower($cat), $tags))
                    $arts[] = $a;
            }
        } elseif (!empty($reg)) {
            foreach ($arts_wtags as $k => $a) {
                $tags = explode(';', $a->tags_alias);
                if(in_array(mb_strtolower($reg), $tags))
                    $arts[] = $a;
            }
        }
    }
    return array_slice($arts, 0, 4);
}

//Function to print HTML
function _printArtileHTML($id,$catid,$title,$introtext,$urlimg)
{
    $url= JRoute::_(ContentHelperRoute::getArticleRoute($id, $catid));
    $introSplit = explode('::introtext::', $introtext);
    $introtext = array_key_exists(1, $introSplit) ? $introSplit[1] : $introtext;

?>
<div class="newsbox col-lg-3 col-md-6 col-sm-6">
    <div class="border-box">
        <a href="<?php echo $url ?>" title="<?php echo $title ?>">
            <div class="article-img" style="background-image:url(<?php echo $urlimg ?>)"></div>
        </a>
        <h3>
            <a href="<?php echo $url ?>">
                <?php echo mb_substr(html_entity_decode($title), 0, 50) ?>
            </a>
        </h3>
        <p>
            <?php echo mb_substr(strip_tags($introtext),0,110) ?>
        </p>
    </div>
</div>
<?php
}

//Function first block
function ConectAll($regiao="",$cattag="",$catblock1){
    $res=_queryArticles($regiao,$cattag,$catblock1);
    if(!empty($res)>0){
        echo '<div class="container">';
        echo '<div class="row">';
        foreach($res as $r){
            $urlimg=json_decode($r->images)->image_fulltext;
            _printArtileHTML($r->id,$r->catid,$r->art_title,$r->introtext, $urlimg);
        }
        echo '</div>';
        echo '</div>';
    } else if($regiao !="PT"){
        ConectAll("PT",$cattag,$catblock1);
    }
}
//Function to call region and cat
function ConectEvents($regiao="",$cattag="",$catblock2){
    $res=_queryArticles($regiao,$cattag,$catblock2);
    if(!empty($res)>0){
        echo '<div class="container">';
        echo '<div class="row greybox">';
        foreach($res as $r){
            $urlimg=json_decode($r->images)->image_fulltext;
            _printArtileHTML($r->id,$r->catid,$r->art_title,$r->introtext, $urlimg);
        }
        echo '</div>';
        echo '</div>';
    }
    else if($regiao !="PT"){
        ConectEvents("PT",$cattag,$catblock2);
    }
}?>

<!--This script is not necessary if you just want load the element one time, this function is to print the name of the country and categories-->
<script language="javascript" type="text/javascript">

    function SetCountryName(code) {
        var tmp = 'Sem filtro regional';
        if (code != 'todos') {
            var name = jQuery('a[data-country=' + code + ']').html();
            tmp = 'Filtrado para ' + name;
        }
        jQuery("#filter-country-selected").html(tmp);
    }

    function SetTopicSelected(code) {
        if (code == '')
            code = 'tudo';

        var el = jQuery('a[data-topic=' + code + ']');
        if (el) {
            jQuery(".filter-topics li").removeClass("current active");
            el.parent().addClass("current active");
        }
    }

    jQuery(document).ready(function () {

        SetCountryName('<?php echo $regiao ?>');
        SetTopicSelected('<?php echo $cattag ?>');
    });

</script>