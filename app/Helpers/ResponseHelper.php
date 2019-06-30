<?php

class ResponseHelper {

    private function __construct() {}

    public static function generatePaginationLinks($url, $total, $limit = 10, $offset = 0) {
        $links = [];
        $links['first'] = $url . '?page[limit]=' . $limit . '&page[offset]=0';
        $links['last'] = $url . '?page[limit]=' . $limit . '&page[offset]=' . (intdiv($total - 1, $limit)) * $limit;
        $links['next'] = (intdiv($total - 1, $limit) * $limit >= $offset + $limit) ?
            ($url . '?page[limit]=' . $limit . '&page[offset]=' . ($offset + $limit)) : null;
        $links['prev'] = ($offset - $limit >= 0) ?
            ($url . '?page[limit]=' . $limit . '&page[offset]=' . ($offset - $limit)) : null;

        return $links;
    }

}