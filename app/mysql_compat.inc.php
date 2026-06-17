<?php
/**
 * Couche de compatibilité mysql_* → mysqli pour PHP 8.x
 *
 * Réimplémente les fonctions mysql_* (supprimées en PHP 7) à l'aide de mysqli,
 * afin de faire tourner le code legacy NPVB sans le réécrire intégralement.
 *
 * Chargé automatiquement via auto_prepend_file (pool PHP-FPM nantespvb).
 * Le "dernier lien" ouvert est mémorisé pour émuler le comportement historique
 * où le paramètre $link est optionnel.
 */

if (!function_exists('mysql_connect')) {

    // PHP 8.1+ active les exceptions mysqli par défaut. Le code legacy attend
    // un retour false en cas d'erreur (comportement PHP 4) → on rétablit ce mode.
    mysqli_report(MYSQLI_REPORT_OFF);

    $GLOBALS['__mysql_compat_link'] = null;

    function __mysql_compat_link($link = null) {
        if ($link instanceof mysqli) return $link;
        return $GLOBALS['__mysql_compat_link'];
    }

    function mysql_connect($host = null, $user = null, $pass = null) {
        // Support de l'hôte sous forme "host:port"
        $port = null;
        if ($host !== null && strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
            $port = (int)$port;
        }
        $link = @mysqli_connect($host, $user, $pass, '', $port ?: 3306);
        if ($link) {
            $GLOBALS['__mysql_compat_link'] = $link;
            @mysqli_set_charset($link, 'utf8mb4');
            // Mode non-strict : reproduit le comportement permissif de l'ancien MySQL
            // (Free), p.ex. '' → 0 pour les colonnes INT, dates 0000-00-00 acceptées.
            @mysqli_query($link, "SET SESSION sql_mode=''");
        }
        return $link ?: false;
    }

    function mysql_select_db($db, $link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_select_db($l, $db) : false;
    }

    function mysql_query($query, $link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_query($l, $query) : false;
    }

    function mysql_fetch_assoc($result) {
        return ($result instanceof mysqli_result) ? mysqli_fetch_assoc($result) : false;
    }

    function mysql_fetch_object($result) {
        return ($result instanceof mysqli_result) ? mysqli_fetch_object($result) : false;
    }

    function mysql_fetch_row($result) {
        return ($result instanceof mysqli_result) ? mysqli_fetch_row($result) : false;
    }

    function mysql_fetch_array($result, $type = MYSQLI_BOTH) {
        return ($result instanceof mysqli_result) ? mysqli_fetch_array($result, $type) : false;
    }

    function mysql_num_rows($result) {
        return ($result instanceof mysqli_result) ? mysqli_num_rows($result) : 0;
    }

    function mysql_real_escape_string($str, $link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_real_escape_string($l, (string)$str) : addslashes((string)$str);
    }

    function mysql_error($link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_error($l) : mysqli_connect_error();
    }

    function mysql_errno($link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_errno($l) : mysqli_connect_errno();
    }

    function mysql_insert_id($link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_insert_id($l) : 0;
    }

    function mysql_affected_rows($link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_affected_rows($l) : -1;
    }

    function mysql_set_charset($charset, $link = null) {
        $l = __mysql_compat_link($link);
        return $l ? mysqli_set_charset($l, $charset) : false;
    }

    function mysql_close($link = null) {
        $l = __mysql_compat_link($link);
        if ($l) {
            $ok = mysqli_close($l);
            if ($l === $GLOBALS['__mysql_compat_link']) {
                $GLOBALS['__mysql_compat_link'] = null;
            }
            return $ok;
        }
        return false;
    }

    function mysql_result($result, $row, $field = 0) {
        if (!($result instanceof mysqli_result)) return false;
        mysqli_data_seek($result, $row);
        $r = mysqli_fetch_array($result, MYSQLI_BOTH);
        return isset($r[$field]) ? $r[$field] : false;
    }

    function mysql_data_seek($result, $row) {
        return ($result instanceof mysqli_result) ? mysqli_data_seek($result, $row) : false;
    }

    function mysql_num_fields($result) {
        return ($result instanceof mysqli_result) ? mysqli_num_fields($result) : 0;
    }
}
