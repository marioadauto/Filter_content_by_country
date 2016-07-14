<?php 
define( '_JEXEC', 1 );
define( 'JPATH_BASE', realpath(dirname(__FILE__).'/../..' ));
define( 'DS', DIRECTORY_SEPARATOR );


require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );
require_once (JPATH_BASE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');
JFactory::getApplication('site');


//get block categories
$catblock1 = $_COOKIE["categoriaselecionada"];
$catblock2 = $_COOKIE["categoriaselecionadab"];


// Check if have cookie or not, the cookie will define the filter for change for  geolocation
if($_COOKIE['Pais'] == "todos")
    {   
       $regiao = "";
    } else {
      $regiao = $_COOKIE["Pais"];
}

if(strval($_GET['tagid']) != NULL)
{   
    //define the name of variable that you will get
    $cattag = strval($_GET['tagid']);
    //Function to call
    ConectAll($regiao,$cattag,$catblock1);
    ConectEvents($regiao,$cattag,$catblock2);
}
elseif(isset($_COOKIE['tag']))
{   
     $cattag = $_COOKIE['tag'];    
  
     //Function to call
    ConectAll($regiao,$cattag,$catblock1);
    ConectEvents($regiao,$cattag,$catblock2);
} 
else {

     $cattag = "";
     ConectAllEverything($regiao,$catblock1);
     ConectEventsEverything($regiao,$catblock2);
}




/**
 * Returns the result of a Joomla query to the articles and dpfields tables, In this case we need to got 2 tables
 * @param string $reg region code
 * @param int $cat category id
 * @return array with query results
 */
function _queryArticles($reg="",$cat="",$catsel)
{
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $arts = array();
    $query->select(array(
        $db->qn('con.id'),
        $db->qn('con.title','art_title'),
        $db->qn('con.alias'),
        $db->qn('con.images'),
        $db->qn('con.introtext'),
        $db->qn('con.catid'),
         "GROUP_CONCAT(".$db->qn('tag.alias')." SEPARATOR ';') AS tags_alias",
         "GROUP_CONCAT(".$db->qn('tag.title')." SEPARATOR ';') AS tags_title",
        ));
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
    return array_slice($arts, 0, 4);
}


function _printArtileHTML($id,$catid,$title,$introtext,$urlimg)
{
    $url= mb_strstr(JRoute::_(ContentHelperRoute::getArticleRoute($id, $catid)), 'sobre/');
    $introSplit = explode('::introtext::', $introtext);
    $introtext = array_key_exists(1, $introSplit) ? $introSplit[1] : $introtext;

     ?>
    <div class="newsbox col-lg-3 col-md-6 col-sm-6">
        <div class="border-box"> 
            <a href="<?php echo $url ?>" title="<?php echo $title ?>">
                <div class="article-img" style="background-image:url(<?php echo $urlimg ?>)"></div>   
            </a>
            <h3><a href="<?php echo $url ?>"><?php echo mb_substr(html_entity_decode($title), 0, 50) ?></a></h3>
            <p><?php echo mb_substr(strip_tags($introtext),0,110) ?></p>
        </div>
    </div>
    <?php
}
//FIRST BLOCk
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
    } 
     else{
         if($regiao !="PT"&&!empty($regiao)){
            ConectAll("PT",$cattag,$catblock1);
         }
         else{
         }
       
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
    else{
       if($regiao !="PT"&&!empty($regiao)){
            ConectEvents("PT",$cattag,$catblock2);
         }
         else{
         }
    }
    
}
 
//Query for every articles
function _queryArticlesEver($reg="",$cat=0)
{
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select(array(
        $db->qn('con.id'),
        $db->qn('con.title','art_title'),
        $db->qn('con.alias'),
        $db->qn('con.images'),
        $db->qn('con.introtext'),
        $db->qn('con.catid'),
         "GROUP_CONCAT(".$db->qn('tag.title')." SEPARATOR ';') AS tag_title",
        )
    );
 
    $query->from($db->qn('#__content', 'con'));
    $query->innerJoin($db->qn('#__contentitem_tag_map', 'tm') . ' ON (' . $db->qn('con.id') . ' = ' . $db->qn('tm.content_item_id') . ')');
    $query->innerJoin($db->qn('#__tags', 'tag') . ' ON (' . $db->qn('tag.id') . ' = ' . $db->qn('tm.tag_id') . ')');
    $query ->where($db->qn('con.state').' = 1');
    $query ->where($db->qn('con.catid').'='.$db->q($cat));
    if(!empty($reg)) {
            if(is_array($reg))
                $query->where($db->qn('tag.alias')." IN ('".implode("','",$reg)."')");
            else
                $query->where($db->qn('tag.alias')." = ".$db->q(strtolower($reg)));
    }      
    $query ->group($db->qn('con.id'));
    $query ->order($db->qn('con.publish_up') . ' DESC');    
    $query->setLimit('4');      

    $db->setQuery($query);
    // echo "<script>console.log('".$query."')</script>";
    return $db->loadObjectList();
}
//FIRST BLOCk for every Articles
function ConectAllEverything($regiao="",$catblock1){
    
    $res=_queryArticlesEver($regiao,$catblock1);   
    if(!empty($res)>0){
        echo '<div class="codecountry">';
        echo '</div>';
        echo '<div class="container">';
        echo '<div class="row">';
        foreach($res as $r){
             $urlimg=json_decode($r->images)->image_fulltext;
            _printArtileHTML($r->id,$r->catid,$r->art_title,$r->introtext, $urlimg);
        }
        echo '</div>';
        echo '</div>';
    } 
     else{
         if($regiao !="PT"&&!empty($regiao)){
            ConectAllEverything("PT",$catblock1);
         }
         else{
         }
       
    }
}
//Function to call region and cat every Articles
function ConectEventsEverything($regiao="",$catblock2){
    $res=_queryArticlesEver($regiao,$catblock2);
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
    else{
       if($regiao !="PT"&&!empty($regiao)){
            ConectEventsEverything("PT",$catblock2);
         }
         else{
         }
    }
    
}




?>
