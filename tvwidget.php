<?php
  /**
   *  Plugin Name: TvWidget
   *  Version: 1.1
   *  Plugin URI: http://www.tvlive.im
   *  Description: Acest plugin va permite sa alegeti posturile tv favorite si sa le afisati sub forma unui widget, pe blogurile sau siet-urile personale.
   *  Author: Alexandrescu Claudiu
   *  Author URI: http://www.tvlive.im 
   **/

   
  include_once(ABSPATH . WPINC . '/feed.php');   
  
   define('TV_API_PROVIDER', 'http://www.tvlive.im/');
   define('TV_API_URL', TV_API_PROVIDER.'api/index.php');
   define('TV_API_WIDTH', 500);
   define('TV_API_HEIGHT', 500);
         
  
   function getCategAPI(){
    
    $data = array('method' => 'getCateg', 'time' => time());
    $query = http_build_query($data);
    
    $rss = fetch_feed(TV_API_URL.'?'.$query); 
    $items = $rss->get_items();
    
    foreach($items as $item){
      $categ = $item->data['data'];
      
      if(get_option('tw_categ_bifate') == TRUE){
        $categBifate = unserialize(get_option('tw_categ_bifate'));
        $checked = (in_array($categ, $categBifate)) ? 'checked' : '';
      } else {
        $checked = '';
      }
      echo '<input type="checkbox" name="categorii[]" value="'.$categ.'" '.$checked.'/> '.$categ.' ';
    }
   }

  
   function getTvsCategAPI(){
    
    $categBifate = unserialize(get_option('tw_categ_bifate')); 
    $data = array('method' => 'getTvsCateg', 'cats' => $categBifate, 'time' => time());
    $query = http_build_query($data);
    
    $rss = fetch_feed(TV_API_URL.'?'.$query);
    $items = $rss->get_items();
    
    foreach($items as $item){
      $post = $item->data['data'];
      $idpost = $item->data['attribs']['']['idpost'];

      if(get_option('tw_post_bifate') == TRUE) {
        $postBifate = unserialize(get_option('tw_post_bifate'));
        $checked = (in_array($idpost, $postBifate)) ? 'checked' : '';
      } else  {
        $checked = '';
      }
      echo '<input type="checkbox" name="posturi[]" value="'.$idpost.'" '.$checked.'/> <a href="'.TV_API_PROVIDER.'post-'.$idpost.'.html" onclick="javascript: window.open(\''.TV_API_PROVIDER.'post-'.$idpost.'.html\', \'\', \'width='.TV_API_WIDTH.', height='.TV_API_HEIGHT.'\'); return false;" target="_blank">'.$post.'</a> ';
    }
   }
  
  
   function getTvsAPI(){ // este setat automat la maxim 5 posturi tv per categorie
    
    // Afisam widget doar daca exista posturi radio bifate
    if(get_option('tw_post_bifate') == TRUE){
      $max = get_option('tw_widget_max');
            
      $postBifate = unserialize(get_option('tw_post_bifate'));
      $data = array('method' => 'getTvs', 'ids' => $postBifate);
      $query = http_build_query($data);
      $rss = fetch_feed(TV_API_URL.'?'.$query);
      $items = $rss->get_items();

      echo '<ul>';
      foreach($items as $item){
        $numecat = $item->data['attribs']['']['numecat'];
      
        echo '<li><strong style="font-size: 14px;">'.$numecat.'</strong>';
        $i=1;
		echo '<ul>';
        foreach($item->data['child']['']['post'] as $post){
          $numepost = $post['data'];
          $idpost = $post['attribs']['']['idpost'];
		         
          if($i<=$max) echo '<li><a href="'.TV_API_PROVIDER.'post-'.$idpost.'.html" onclick="javascript: window.open(\''.TV_API_PROVIDER.'post-'.$idpost.'.html\', \'\', \'width='.TV_API_WIDTH.', height='.TV_API_HEIGHT.'\'); return false;" target="_blank" rel="follow" title="TvOnline '.$numepost.'">'.$numepost.'</a></li>';
          $i++;
        }
        echo '</ul></li>';
      }
      echo '<a  href="'.TV_API_PROVIDER.'" title="Tv Online" target="_blank">by TvLive.im</a>';
      echo '</ul>'; 
    } else {
      echo '<ul><li>Nu ati selectat nici un post tv!</li></ul>';
    }
   }
   
  
  function register_tw_widget($args) {
    extract($args);

    $title = get_option('tw_widget_title');
    echo $args['before_widget'];
    echo $args['before_title'].' '.$title.' '.$args['after_title'];
    getTvsAPI();
    echo $args['after_widget']; 
  }
  	  
  function register_tw_control(){
    $max = get_option('tw_widget_max');
    $title = get_option('tw_widget_title');
    
    echo '<p><label>Titlu TvWidget: <input name="title" type="text" value="'.$title.'" /></label></p>';
    echo '<p><label>Posturi Tv / categorie: <input name="max" type="text" value="'.$max.'" /></label></p>';
      
    if(isset($_POST['max'])){
      update_option('tw_widget_max', attribute_escape($_POST['max']));
      update_option('tw_widget_title', attribute_escape($_POST['title']));
    }
  }    
  
  function tw_widget() {
  	 register_widget_control('TvWidget', 'register_tw_control'); 
  	 register_sidebar_widget('TvWidget', 'register_tw_widget');
  }          
   
  
   function tw_admin(){
    echo '<div class="wrap">';
    echo '<h2>Setari TvWidget</h2>';
    if(isset($_POST['scategorii']) && isset($_POST['categorii'])){ 
        $categorii = serialize($_POST['categorii']);
        if(get_option('tw_categ_bifate') === FALSE){
          add_option('tw_categ_bifate', $categorii);
        } else {
          delete_option('tw_categ_bifate');
          add_option('tw_categ_bifate', $categorii);
        }
    }
    echo '<div class="widefat" style="padding: 5px">1) Alege una sau mai multe categorii:<br /><br />';
    echo '<form method="post" name="categorii" target="_self">';
    getCategAPI();
    echo '<input name="scategorii" type="hidden" value="yes" />';
    echo '<br /><br /><input type="submit" name="Submit" value="Listeaza posturile Tv &raquo;" />';    
    echo '</form>';
    echo '</div>';
    echo '<br />';
    if(isset($_POST['scategorii']) && isset($_POST['categorii'])){
      echo '<div class="widefat fade" style="padding: 5px">2) Alege posturile Tv pe care vrei sa le afisezi <br /><br />';
      echo '<form method="post" name="posturi" target="_self">';      
      getTvsCategAPI();
      echo '<input name="sposturi" type="hidden" value="yes" />';
      echo '<br /><br /><input type="submit" name="Submitt" value="Salveaza  &raquo;" />';    
      echo '</form>';
      echo '</div>';   
    }
    if(isset($_POST['sposturi']) && isset($_POST['posturi'])){
        $posturi = serialize($_POST['posturi']);
        if(get_option('tw_post_bifate') === FALSE){
          add_option('tw_post_bifate', $posturi);
        } else {
          delete_option('tw_post_bifate');
          add_option('tw_post_bifate', $posturi);
        }
        echo '<div id="message" class="updated fade"><p><strong>Posturile Tv au fost salvate !</strong></p></div>';        
      }    
    echo '</div>';
   }

  
  function tw_addpage() {
    add_menu_page('TV Widget', 'TV Widget', 10, __FILE__, 'tw_admin');
  }
  
  
  function tw_install(){
    add_option('tw_widget_max', '5');
    add_option('tw_widget_title', 'Tv Online');
  }
  
  function tw_uninstall(){
    delete_option('tw_widget_max');
    delete_option('tw_widget_title');
    delete_option('tw_post_bifate');
    delete_option('tw_categ_bifate');
  }     
  
  
  add_action('admin_menu', 'tw_addpage');
  add_action("plugins_loaded", 'tw_widget');
  register_activation_hook(__FILE__, 'tw_install');
  register_deactivation_hook(__FILE__, 'tw_uninstall');    
?>
