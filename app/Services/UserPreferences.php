<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Xinax\LaravelGettext\Facades\LaravelGettext;
use App\Facades\AccessService;

class UserPreferences
{
    public function __construct()
    {
    }
    
     public function create_preference_input($name, $value, $id)
    {
        if (!$this->has_access($name)) {
            if ($value == '1') {
                echo "Enabled";
            } elseif ($value == '0') {
                echo "Disabled";
            } else {
                if (preg_match('/_pass$/', $name) || preg_match('/_api_key$/', $name)) {
                    echo "******";
                } else {
                    echo $value;
                }
            }
            
            return;
        } // if we don't have access to it
        
        switch ($name) {
            case 'display_menu':
            case 'download':
            case 'quarantine':
            case 'upload':
            case 'access_list':
            case 'lock_songs':
            case 'xml_rpc':
            case 'force_http_play':
            case 'no_symlinks':
            case 'use_auth':
            case 'access_control':
            case 'allow_stream_playback':
            case 'allow_democratic_playback':
            case 'allow_localplay_playback':
            case 'demo_mode':
            case 'condPL':
            case 'rio_track_stats':
            case 'rio_global_stats':
            case 'direc__link':
            case 'ajax_load':
            case 'now_playing_per_user':
            case 'show_played_times':
            case 'song_page_title':
            case 'subsonic_backend':
            case 'plex_backend':
            case 'webplayer_flash':
            case 'webplayer_html5':
            case 'allow_personal_info_now':
            case 'allow_personal_info_recent':
            case 'allow_personal_info_time':
            case 'allow_personal_info_agent':
            case 'ui_fixed':
            case 'autoupdate':
            case 'webplayer_confirmclose':
            case 'webplayer_pausetabs':
            case 'stream_beautiful_url':
            case 'share':
            case 'share_social':
            case 'broadcas__by_default':
            case 'album_group':
            case 'topmenu':
            case 'demo_clear_sessions':
            case 'show_donate':
            case 'allow_upload':
            case 'upload_subdir':
            case 'upload_user_artist':
            case 'upload_allow_edit':
            case 'daap_backend':
            case 'upnp_backend':
            case 'album_release_type':
            case 'home_momen__albums':
            case 'home_momen__videos':
            case 'home_recently_played':
            case 'home_now_playing':
            case 'browser_notify':
            case 'allow_video':
            case 'geolocation':
            case 'webplayer_aurora':
            case 'upload_allow_remove':
            case 'webdav_backend':
            case 'notify_email':
            case 'libitem_contextmenu':
            case 'upload_catalog_pattern':
            case 'catalogfav_gridview':
            case 'browse_filter':
            case 'sidebar_light':
                $is_true  = '';
                $is_false = '';
                if ($value == '1') {
                    $is_true = "selected=\"selected\"";
                } else {
                    $is_false = "selected=\"selected\"";
                }
                echo "<select class=\"w3-small\" name=\"$name\">\n";
                echo "\t<option value=\"1\" $is_true>" . __("Enable") . "</option>\n";
                echo "\t<option value=\"0\" $is_false>" . __("Disable") . "</option>\n";
                echo "</select>\n";
                break;
            case 'upload_catalog':
                $this->show_catalog_select('upload_catalog', $value, '', true);
                break;
            case 'play_type':
                $is_localplay  = '';
                $is_democratic = '';
                $is_web_player = '';
                $is_stream     = '';
                if ($value == 'localplay') {
                    $is_localplay = 'selected="selected"';
                } elseif ($value == 'democratic') {
                    $is_democratic = 'selected="selected"';
                } elseif ($value == 'web_player') {
                    $is_web_player = 'selected="selected"';
                } else {
                    $is_stream = "selected=\"selected\"";
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"\">" . __('None') . "</option>\n";
                if (AmpConfig::get('allow_stream_playback')) {
                    echo "\t<option value=\"stream\" $is_stream>" . __('Stream') . "</option>\n";
                }
                if (AmpConfig::get('allow_democratic_playback')) {
                    echo "\t<option value=\"democratic\" $is_democratic>" . __('Democratic') . "</option>\n";
                }
                if (AmpConfig::get('allow_localplay_playback')) {
                    echo "\t<option value=\"localplay\" $is_localplay>" . __('Localplay') . "</option>\n";
                }
                echo "\t<option value=\"web_player\" $is_web_player>" . __('Web Player') . "</option>\n";
                echo "</select>\n";
                break;
            case 'playlis__type':
                $var_name    = $value . "_type";
                ${$var_name} = "selected=\"selected\"";
                echo "<select class=\"w3-small\" name=\"$name\">\n";
                echo "\t<option value=\"m3u\" $m3u_type>" . __('M3U') . "</option>\n";
                echo "\t<option value=\"simple_m3u\" $simple_m3u_type>" . __('Simple M3U') . "</option>\n";
                echo "\t<option value=\"pls\" $pls_type>" . __('PLS') . "</option>\n";
                echo "\t<option value=\"asx\" $asx_type>" . __('Asx') . "</option>\n";
                echo "\t<option value=\"ram\" $ram_type>" . __('RAM') . "</option>\n";
                echo "\t<option value=\"xspf\" $xspf_type>" . __('XSPF') . "</option>\n";
                echo "</select>\n";
                break;
            case 'lang':                
                $locales = config('laravel-gettext.supported-locales');
                $languages = config('languages');
                
                echo  '<select class="w3-small" name="' . $name . '">' . "\n";
                foreach ($locales as $lang => $name) {
                    $selected = ($lang == $value) ? 'selected="selected"' : '';
                    echo "\t<option value=\"$lang\" " . $selected . ">$languages[$name]</option>\n";
                } // end foreach
               echo "</select>\n";
               
                break;
 /*           case 'localplay_controller':
                $controllers = Localplay::ge__controllers();
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"\">" . __('None') . "</option>\n";
                foreach ($controllers as $controller) {
                    if (!Localplay::is_enabled($controller)) {
                        continue;
                    }
                    $is_selected = '';
                    if ($value == $controller) {
                        $is_selected = 'selected="selected"';
                    }
                    echo "\t<option value=\"" . $controller . "\" $is_selected>" . ucfirst($controller) . "</option>\n";
                } // end foreach
                echo "</select>\n";
                break;
 
            case 'localplay_level':
                $is_user    = '';
                $is_admin   = '';
                $is_manager = '';
                if ($value == '25') {
                    $is_user = 'selected="selected"';
                } elseif ($value == '100') {
                    $is_admin = 'selected="selected"';
                } elseif ($value == '50') {
                    $is_manager = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "<option value=\"0\">" . __('Disabled') . "</option>\n";
                echo "<option value=\"25\" $is_user>" . __('User') . "</option>\n";
                echo "<option value=\"50\" $is_manager>" . __('Manager') . "</option>\n";
                echo "<option value=\"100\" $is_admin>" . __('Admin') . "</option>\n";
                echo "</select>\n";
                break;
 */
            case 'playlist_method':
                ${$value} = ' selected="selected"';
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"send\"$send>" . __('Send on Add') . "</option>\n";
                echo "\t<option value=\"send_clear\"$send_clear>" . __('Send and Clear on Add') . "</option>\n";
                echo "\t<option value=\"clear\"$clear>" . __('Clear on Send') . "</option>\n";
                echo "\t<option value=\"default\"$default>" . __('Default') . "</option>\n";
                echo "</select>\n";
                break;
            case 'transcode':
                ${$value} = ' selected="selected"';
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"never\"$never>" . __('Never') . "</option>\n";
                echo "\t<option value=\"default\"$default>" . __('Default') . "</option>\n";
                echo "\t<option value=\"always\"$always>" . __('Always') . "</option>\n";
                echo "</select>\n";
                break;
            case 'show_lyrics':
                $is_true  = '';
                $is_false = '';
                if ($value == '1') {
                    $is_true = "selected=\"selected\"";
                } else {
                    $is_false = "selected=\"selected\"";
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"1\" $is_true>" . __("Enable") . "</option>\n";
                echo "\t<option value=\"0\" $is_false>" . __("Disable") . "</option>\n";
                echo "</select>\n";
                break;
            case 'album_sort':
                $is_sor__year_asc  = '';
                $is_sor__year_desc = '';
                $is_sor__name_asc  = '';
                $is_sor__name_desc = '';
                $is_sor__default   = '';
                if ($value == 'year_asc') {
                    $is_sor__year_asc = 'selected="selected"';
                } elseif ($value == 'year_desc') {
                    $is_sor__year_desc = 'selected="selected"';
                } elseif ($value == 'name_asc') {
                    $is_sor__name_asc = 'selected="selected"';
                } elseif ($value == 'name_desc') {
                    $is_sor__name_desc = 'selected="selected"';
                } else {
                    $is_sor__default = 'selected="selected"';
                }
                
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"default\" $is_sor__default>" . __('Default') . "</option>\n";
                echo "\t<option value=\"year_asc\" $is_sor__year_asc>" . __('Year ascending') . "</option>\n";
                echo "\t<option value=\"year_desc\" $is_sor__year_desc>" . __('Year descending') . "</option>\n";
                echo "\t<option value=\"name_asc\" $is_sor__name_asc>" . __('Name ascending') . "</option>\n";
                echo "\t<option value=\"name_desc\" $is_sor__name_desc>" . __('Name descending') . "</option>\n";
                echo "</select>\n";
                break;
 /*
            case 'disabled_custom_metadata_fields':
                $ids             = explode(',', $value);
                $options         = array();
                $fieldRepository = new \Lib\Metadata\Repository\MetadataField();
                foreach ($fieldRepository->findAll() as $field) {
                    $selected  = in_array($field->getId(), $ids) ? ' selected="selected"' : '';
                    $options[] = '<option value="' . $field->getId() . '"' . $selected . '>' . $field->getName() . '</option>';
                }
                echo '<select multiple size="5" name="' . $name . '[]">' . implode("\n", $options) . '</select>';
                break;
 */
            case 'lastfm_gran__link':
            case 'librefm_grapreferencesn__link':
                // construct links for granting access Ampache application to Last.fm and Libre.fm
                $plugin_name = ucfirst(str_replace('_gran__link', '', $name));
 //               $plugin      = new Plugin($plugin_name);
                $url         = $plugin->_plugin->url;
                $api_key     = rawurlencode(AmpConfig::get('lastfm_api_key'));
                $callback    = rawurlencode(AmpConfig::get('web_path') . '/preferences.php?tab=plugins&action=grant&plugin=' . $plugin_name);
   //             echo "<a href='$url/api/auth/?api_key=$api_key&cb=$callback'>" . UI::ge__icon('plugin', sprintf(__("Click to grant %s access to Ampache"), $plugin_name)) . '</a>';
                break;
            default:
                if (preg_match('/_pass$/', $name)) {
                    echo '<input type="password" name="' . $name . '" value="******" />';
                } else {
                    echo '<input id="name_' . $id . '" class="w3-small"' .  'type="text" name="' . $name . '" value="' . $value . '" />';
                }
                break;
                
        }
    }

    public  function has_access($preference)
    {
        // Nothing for those demo thugs
        if (config('program.demo_mode')) {
            return false;
        }
        
        $sql        = "SELECT `level` FROM `preference` WHERE `name`='$preference'";
        $db_results = DB::table('preferences')->select('level')->where('name', $preference)->get();
        foreach ($db_results as $result) {
            $data = $result->level;
        }
        if (AccessService::check('interface', $data)) {
            return true;
        }
    
        return false;
    } // has_access


    public function get_all($user_id)
    {
        $user_limit = "";
        if ($user_id != '0') {
            $user_limit = "`preferences`.`category` != 'system'";
        }
        
        if ($user_id != 0) {
            $db_results = DB::table('preferences')->select('preferences.name', 'preferences.description', 'preferences.subcategory', 'preferences.level', 'user_preferences.value')
                  ->join('user_preferences', 'user_preferences.preference', '=', 'preferences.id')
                  ->where([['user_preferences.user', '=', $user_id], ['preferences.category', '<>', 'internal'],
                   ['preferences.category', '<>', 'system']])->get();
        } else {
            $db_results = DB::table('preferences')->select('preferences.name', 'preferences.description', 'preferences.category', 'preferences.subcategory', 'preferences.level', 'user_preferences.value')
        ->join('user_preferences', 'user_preferences.preference', '=', 'preferences.id')
        ->where([['user_preferences.user', '=', $user_id], ['preferences.category', '<>', 'internal']])->get();
        }
    
        foreach ($db_results as $row) {
            $results[$row->name] = array('level' => $row->level,'description' => $row->description,'value' => $row->value,'subcategory' => $row->subcategory);
        }

        return $results;
    } // ge__all

    public static function update_config($user_id)
    {
        $preferences = new UserPreferences();
        $results     = self::get_all($user_id);
        $flattened   = array_dot($results);
        $keys        = array_keys($results);
        foreach ($flattened as $key => $value) {
            config(['program.' . $key => $value]);
        }
    }
    public static function get_languages()
    {
        $results = LaravelGettext::getSelector()->render();
        
        return $results;
    } // get_languages
    
    function show_catalog_select($name='catalog', $catalog_id=0, $style='', $allow_none=false, $filter_type='')
    {
        echo "<select name=\"$name\" style=\"$style\">\n";
        
        $params     = array();
        $sql        = "SELECT `id`, `name` FROM `catalog` ";
        if (!empty($filter_type)) {
            $results = DB::table('Catalogs')->select('id', 'name')->where('gather_types', '=', $filter_type)
            ->orderBy('name', 'asc')->get();
            $sql .= "WHERE `gather_types` = ?";
            $params[] = $filter_type;
        } else {
            $results = DB::table('Catalogs')->select('id', 'name')
            ->get();
        }
        $sql .= "ORDER BY `name`";
        $db_results = Dba::read($sql, $params);
        
        if ($allow_none) {
            echo "\t<option value=\"-1\">" . T_('None') . "</option>\n";
        }
        
        while ($r = Dba::fetch_assoc($db_results)) {
            $selected = '';
            if ($r['id'] == $catalog_id) {
                $selected = "selected=\"selected\"";
            }
            
            echo "\t<option value=\"" . $r['id'] . "\" $selected>" . scrub_out($r['name']) . "</option>\n";
        } // end while
        
        echo "</select>\n";
    } // show_catalog_select
    
}
