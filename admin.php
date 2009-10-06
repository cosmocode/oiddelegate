<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
require_once(DOKU_INC.'inc/changelog.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_oiddelegate extends DokuWiki_Admin_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }


    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 80;
    }

    /**
     * handle user request
     */
    function handle() {
        if(!is_array($_REQUEST['oiddel'])) return;

        // prepare new config file
        $data = "# OpenID Delegation Setup\n\n";
        foreach($_REQUEST['oiddel'] as $line){
            $line = array_map('trim',$line);
            // make sure OpenIDs and servers are given as full qualified URLs
            if($line[1] && !preg_match('#^https?://#',$line[1])){
                $line[1] = 'http://'.$line[1];
            }
            if($line[2] && !preg_match('#^https?://#',$line[2])){
                $line[2] = 'http://'.$line[2];
            }

            $data .= join("\t",$line)."\n";
        }

        //save it
        if(io_saveFile(DOKU_CONF.'openid-delegates.conf',$data)){
            msg($this->getLang('saved'),1);
        }
    }

    /**
     * output appropriate html
     */
    function html() {
        global $lang;

        echo $this->plugin_locale_xhtml('intro');


        echo '<form action="" method="post">';
        echo '<table class="inline" id="openid__delegates">';

        echo '<tr>';
        echo '<th width="20%">'.$this->getLang('page').'</th>';
        echo '<th width="40%">'.$this->getLang('oid').'</th>';
        echo '<th width="40%">'.$this->getLang('server').'</th>';
        echo '</tr>';

        $delegates = confToHash(DOKU_CONF.'openid-delegates.conf');
        ksort($delegates);
        $row = 0;
        foreach($delegates as $page => $delegate){
            list($oid,$server) = preg_split('/\s+/',$delegate,2);
            $oid = trim($oid);
            $server = trim($server);

            echo '<tr>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][0]" value="'.hsc($page).'" /></td>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][1]" value="'.hsc($oid).'" /></td>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][2]" value="'.hsc($server).'" /></td>';
            echo '</tr>';

            $row++;
        }
        echo '<tr>';
        echo '<td><input type="text" class="edit" name="oiddel['.$row.'][0]" value="" /></td>';
        echo '<td><input type="text" class="edit" name="oiddel['.$row.'][1]" value="" /></td>';
        echo '<td><input type="text" class="edit" name="oiddel['.$row.'][2]" value="" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th colspan="3" align="center">';
        echo '<input type="submit" value="'.$this->getLang('submit').'" class="button" />';
        echo '</th>';
        echo '</tr>';

        echo '</table>';
        echo '</form>';
    }


}
//Setup VIM: ex: et ts=4 enc=utf-8 :
