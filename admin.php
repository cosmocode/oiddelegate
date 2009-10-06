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


        // new entry? try to detect values
        if(is_array($_REQUEST['oidadd'])){
            $new = array();
            $new[0] = trim($_REQUEST['oidadd'][0]);
            $new[1]  = trim($_REQUEST['oidadd'][1]);
            if($new[0] && $new[1]){
                if(!preg_match('#^https?://#',$new[1])) $new[1] = 'http://'.$new[1];

                // get OID page
                require_once(DOKU_INC.'inc/HTTPClient.php');
                $http = new DokuHTTPClient();
                $http->max_bodysize = 1024*5; // should be enough
                $http->max_bodysize_abort = false;
                $data = $http->get($new[1]);

                if(!$data){
                    msg(sprintf($this->getLang('httperror'),hsc($http->error),-1));
                }

                // match values
                if(preg_match('/<.*?(rel=["\']openid.server["\']).*?>/i',$data,$match)){
                    if(preg_match('/href=["\'](.*?)["\']/i',$match[0],$match)){
                        $new[2] = $match[1];
                    }
                }
                if(preg_match('/<.*?(rel=["\']openid2.provider["\']).*?>/i',$data,$match)){
                    if(preg_match('/href=["\'](.*?)["\']/i',$match[0],$match)){
                        $new[3] = $match[1];
                    }
                }
                if(preg_match('/<.*?(http-equiv=["\']X-XRDS-Location["\']).*?>/i',$data,$match)){
                    if(preg_match('/content=["\'](.*?)["\']/i',$match[0],$match)){
                        $new[4] = $match[1];
                    }
                }
                if($http->resp_headers['x-xrds-location']){
                    $new[4] = $http->resp_headers['x-xrds-location'];
                }
            }else{
                unset($new);
            }
        }


        // prepare new config file
        $data = "# OpenID Delegation Setup\n\n";
        foreach($_REQUEST['oiddel'] as $line){
            $line = array_map('trim',$line);
            if(!$line[0]) continue;

            // make sure OpenIDs and servers are given as full qualified URLs
            for($i=1; $i<5; $i++){
                if($line[$i] && !preg_match('#^https?://#',$line[$i])){
                    $line[$i] = 'http://'.$line[$i];
                }
            }

            $data .= join("\t",$line)."\n";
        }
        // add new entry
        if($new){
            $data .= join("\t",$new)."\n";
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
        echo '<th>'.$this->getLang('page').'</th>';
        echo '<th>'.$this->getLang('oid').'</th>';
        echo '<th>'.$this->getLang('server').'</th>';
        echo '<th>'.$this->getLang('provider').'</th>';
        echo '<th>'.$this->getLang('xrds').'</th>';
        echo '</tr>';

        $delegates = confToHash(DOKU_CONF.'openid-delegates.conf');
        ksort($delegates);
        $row = 0;
        foreach($delegates as $page => $delegate){
            list($oid,$server,$provider,$xrds) = preg_split('/\s+/',$delegate,4);
            $oid      = trim($oid);
            $server   = trim($server);
            $provider = trim($provider);
            $xrds     = trim($provider);

            echo '<tr>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][0]" value="'.hsc($page).'" /></td>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][1]" value="'.hsc($oid).'" /></td>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][2]" value="'.hsc($server).'" /></td>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][3]" value="'.hsc($provider).'" /></td>';
            echo '<td><input type="text" class="edit" name="oiddel['.$row.'][4]" value="'.hsc($xrds).'" /></td>';
            echo '</tr>';

            $row++;
        }


        echo '<tr>';
        echo '<td><input type="text" class="edit" name="oidadd[0]" value="" /></td>';
        echo '<td><input type="text" class="edit" name="oidadd[1]" value="" /></td>';
        echo '<th colspan="3">'.$this->getLang('add').'</th>';
        echo '</tr>';

        echo '<tr>';
        echo '<th colspan="5" align="center">';
        echo '<input type="submit" value="'.$this->getLang('submit').'" class="button" />';
        echo '</th>';
        echo '</tr>';

        echo '</table>';
        echo '</form>';
    }


}
//Setup VIM: ex: et ts=4 enc=utf-8 :
