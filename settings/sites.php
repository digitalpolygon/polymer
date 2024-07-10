<?php

$ddev_hostname = getenv('DDEV_HOSTNAME');
if (!empty($ddev_hostname)) {
    $sites['{http_port}.' . $ddev_hostname] = '{site_name}';
    $sites['{https_port}.' . $ddev_hostname] = '{site_name}';
}
