<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * AutoUpdate Class
 *
 * This class handles autoupdate check from Github.
 */

class AutoUpdate
{
    /*
     * Constructor
     *
     * This should never be called
     */
    private function __construct()
    {
        // static class
    }

    /**
     * Check if current version is a development version.
     * @return boolean
     */
    protected static function is_develop()
    {
        $version = AmpConfig::get('version');
        $vspart  = explode('-', $version);

        return ($vspart[count($vspart) - 1] == 'develop');
    }

    /**
     * Check if current version is a git repository.
     * @return boolean
     */
    protected static function is_git_repository()
    {
        return is_dir(AmpConfig::get('prefix') . '/.git');
    }

    /**
     * Check if branch develop exists in git repository.
     * @return boolean
     */
    protected static function is_branch_develop_exists()
    {
        return is_readable(AmpConfig::get('prefix') . '/.git/refs/heads/develop');
    }

    /**
     * Perform a GitHub request.
     * @param string $action
     * @return string|null
     */
    public static function github_request($action)
    {
        try {
            // https is mandatory
            $url     = "https://api.github.com/repos/ampache/ampache" . $action;
            $request = Requests::get($url, array(), Core::requests_options());

            // Not connected / API rate limit exceeded: just ignore, it will pass next time
            if ($request->status_code != 200) {
                debug_event('autoupdate', 'Github API request ' . $url . ' failed with http code ' . $request->status_code, '1');

                return null;
            }

            return json_decode($request->body);
        } catch (Exception $e) {
            debug_event('autoupdate', 'Request error: ' . $e->getMessage(), '1');

            return null;
        }
    }

    /**
     * Check if last github check expired.
     * @return boolean
     */
    protected static function lastcheck_expired()
    {
        $lastcheck = AmpConfig::get('autoupdate_lastcheck');
        if (!$lastcheck) {
            Preference::update('autoupdate_lastcheck', $GLOBALS['user']->id, '1');
            AmpConfig::set('autoupdate_lastcheck', '1', true);
        }

        return ((time() - (3600 * 3)) > $lastcheck);
    }

    /**
     * Get latest available version from GitHub.
     * @param boolean $force
     * @return string
     */
    public static function get_latest_version($force = false)
    {
        $lastversion = '';
        // Forced or last check expired, check latest version from Github
        if ($force || (self::lastcheck_expired() && AmpConfig::get('autoupdate'))) {
            // Always update last check time to avoid infinite check on permanent errors (proxy, firewall, ...)
            $time = time();
            Preference::update('autoupdate_lastcheck', $GLOBALS['user']->id, $time);
            AmpConfig::set('autoupdate_lastcheck', $time, true);

            // Development version, get latest commit on develop branch
            if (self::is_develop()) {
                $commits = self::github_request('/commits/develop');
                if (!empty($commits)) {
                    $lastversion = $commits->sha;
                    Preference::update('autoupdate_lastversion', $GLOBALS['user']->id, $lastversion);
                    AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                    $available = self::is_update_available(true);
                    Preference::update('autoupdate_lastversion_new', $GLOBALS['user']->id, $available);
                    AmpConfig::set('autoupdate_lastversion_new', $available, true);
                }
            }
            // Otherwise it is stable version, get latest tag
            else {
                $tags = self::github_request('/tags');
                $str  = strstr($tags[0]->name, "pre-release");
                if (!$str) {
                    $lastversion = $tags[0]->name;
                    Preference::update('autoupdate_lastversion', $GLOBALS['user']->id, $lastversion);
                    AmpConfig::set('autoupdate_lastversion', $lastversion, true);
                    $available = self::is_update_available(true);
                    Preference::update('autoupdate_lastversion_new', $GLOBALS['user']->id, $available);
                    AmpConfig::set('autoupdate_lastversion_new', $available, true);
                }
            }
        }
        // Otherwise retrieve the cached version number
        else {
            $lastversion = AmpConfig::get('autoupdate_lastversion');
        }

        return $lastversion;
    }

    /**
     * Get current local version.
     * @return string
     */
    public static function get_current_version()
    {
        if (self::is_develop()) {
            return self::get_current_commit();
        } else {
            return AmpConfig::get('version');
        }
    }

    /**
     * Get current local git commit.
     * @return string
     */
    public static function get_current_commit()
    {
        if (self::is_branch_develop_exists()) {
            return trim(file_get_contents(AmpConfig::get('prefix') . '/.git/refs/heads/develop'));
        }

        return '';
    }

    /**
     * Check if an update is available.
     * @param boolean $force
     * @return boolean
     */
    public static function is_update_available($force = false)
    {
        if (!$force && (!self::lastcheck_expired() || !AmpConfig::get('autoupdate'))) {
            return AmpConfig::get('autoupdate_lastversion_new');
        }

        debug_event('autoupdate', 'Checking latest version online...', '5');

        $available = false;
        $current   = self::get_current_version();
        $latest    = self::get_latest_version();

        if ($current != $latest && !empty($current)) {
            if (self::is_develop()) {
                $ccommit = self::github_request('/commits/' . $current);
                $lcommit = self::github_request('/commits/' . $latest);

                if (!empty($ccommit) && !empty($lcommit)) {
                    // Comparison based on commit date
                    $ctime = strtotime($ccommit->commit->author->date);
                    $ltime = strtotime($lcommit->commit->author->date);

                    $available = ($ctime < $ltime);
                }
            } else {
                $cpart = explode('-', $current);
                $lpart = explode('-', $latest);

                $available = (version_compare($cpart[0], $lpart[0]) < 0);
            }
        }

        return $available;
    }

    /**
     * Display new version information and update link if possible.
     */
    public static function show_new_version()
    {
        echo '<div id="autoupdate">';
        echo '<font color="#ff0000">' . T_('Update available') . '</font>';
        echo ' (' . self::get_latest_version() . ').<br />';

        echo T_('See') . ' <a href="https://github.com/ampache/ampache/' . (self::is_develop() ? 'compare/' . self::get_current_version() . '...' . self::get_latest_version() : 'blob/master/docs/CHANGELOG.md') . '" target="_blank">' . T_('changes') . '</a> ';
        if (self::is_develop()) {
            echo T_('or') . ' <a href="https://github.com/ampache/ampache/archive/' .
             (self::is_develop() ? 'develop.zip' : self::get_latest_version() . '.zip') . '" target="_blank"><b>' . T_('download') . '</b></a>.';
        } else {
            echo T_('or') . ' <a href="https://github.com/ampache/ampache/releases/download/' . self::get_latest_version() .
              '/ampache-' . self::get_latest_version() . '_all.zip"' . ' target="_blank"><b>' . T_('download') . '</b></a>.';
        }
        if (self::is_git_repository()) {
            echo ' | <a rel="nohtml" href="' . AmpConfig::get('web_path') . '/update.php?type=sources&action=update">.:: Update ::.</a>';
        }
        echo '</div>';
    }

    /**
     * Update local git repository.
     */
    public static function update_files()
    {
        $cmd = 'git pull https://github.com/ampache/ampache.git';
        echo T_('Updating Ampache sources with `' . $cmd . '` ...') . '<br />';
        ob_flush();
        chdir(AmpConfig::get('prefix'));
        exec($cmd);
        echo T_('Done') . '<br />';
        ob_flush();
        self::get_latest_version(true);
    }
    
    /**
     * Update project dependencies.
     */
    public static function update_dependencies()
    {
        $cmd = 'composer install --prefer-source --no-interaction';
        echo T_('Updating dependencies with `' . $cmd . '` ...') . '<br />';
        ob_flush();
        chdir(AmpConfig::get('prefix'));
        exec($cmd);
        echo T_('Done') . '<br />';
        ob_flush();
    }
}
