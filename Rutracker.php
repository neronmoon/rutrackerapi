<?php
/**
 * Created by #ROOT.
 * to contact me use skype" => neronmoon
 */

function dd()
{

//    echo "<pre>";
    foreach (func_get_args() as $v) {
        print_r($v);
    }

}

class Rutracker
{

    private static $cookieFile = '/tmp/rutracker_cookies';

    private static $agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13';

    private static $LOGIN_PAGE = 'http://login.rutracker.org/forum/login.php';

    private static $SEARCH_PAGE = 'http://rutracker.org/forum/tracker.php';

    private static $ORDER_OPTIONS = array(
        "date" => 1, "name" => 2, "downloads" => 4, "shows" => 6, "seeders" => 10,
        "leechers" => 11, "size" => 7, "last_post" => 8, "speed_up" => 12,
        "speed_down" => 13, "message_count" => 5, "last_seed" => 9
    );

    private static $SORT_OPTIONS = array("asc" => 1, "desc" => 2);

    public function __construct($username, $pass)
    {
        $this->login($username, $pass);
    }

    /** Advance search throw rutracker
     *
     * @param option [Hash] the format type, `:category`, `:term`, `:sort`, `:order_by`
     * @return array[Hash] the options keys with search result
     */

    public function search($options = array())
    {

        $url = $this->prepare_query_string($options);

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => self::$agent,
            CURLOPT_COOKIEJAR => self::$cookieFile,
            CURLOPT_COOKIEFILE => self::$cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_MAXREDIRS => 10,
        );

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        curl_close($curl);


        return $this->parse_search( $result );
    }


    public function findUser($nick)
    {

        $options = array(
            CURLOPT_URL => 'http://rutracker.org/forum/profile.php?mode=viewprofile&u=' . $nick,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => self::$agent,
            CURLOPT_COOKIEJAR => self::$cookieFile,
            CURLOPT_COOKIEFILE => self::$cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_MAXREDIRS => 10,
        );
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        curl_close($curl);

        $doc = new DOMDocument();
        @$doc->loadHTML($result);

        $role = $this->clean($doc->getElementById('role')->textContent);
        $totalDownloaded = $this->clean($doc->getElementById('u_down_total')->textContent);
        $totalUploaded = $this->clean($doc->getElementById('u_up_total')->textContent);
        $regDate = $this->clean($doc->getElementById('user_regdate')->textContent);

        return array(
            'role' => $role,
            'totalDownloaded' => $totalDownloaded,
            'totalUploaded' => $totalUploaded,
            'registerDate' => $regDate,
        );

    }

    private function login($username, $pass)
    {

        $options = array(
            CURLOPT_URL => self::$LOGIN_PAGE,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => true,
            CURLOPT_USERAGENT => self::$agent,
            CURLOPT_COOKIEJAR => self::$cookieFile,
            CURLOPT_COOKIEFILE => self::$cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_POSTFIELDS => array(
                "login_username" => $username,
                "login_password" => $pass,
                "login" => "Вход"
            )
        );
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        curl_exec($curl);
        curl_close($curl);
    }

    private function prepare_query_string($options = array())
    {
        @$opt = array(
            "f" => $options['category'],
            "nm" => $options['term'],
            "s" => self::$SORT_OPTIONS[$options['sort']],
            "o" => self::$ORDER_OPTIONS[$options['order_by']],
            "start" => $options['page'] * 50
        );

        $opt = array_filter($opt);
        $url = self::$SEARCH_PAGE . '?';

        $i = 0;
        foreach ($opt as $k => $v) {
            $url .= $k . '=' . $v;
            if ($i < count($opt) - 1) $url .= "&";
            $i++;
        }

        return $url;
    }

    private function parse_search( $html )
    {

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $finder = new DomXPath($doc);
        $torrents = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' hl-tr ')]");
        $links = $finder->query("//*[contains(@class, 'med tLink hl-tags bold')]");

        $result = array();
        for( $i = 0; $i < $torrents->length; $i++ ){

            $torrent = $torrents->item($i);

            $status = $this->clean($torrent->childNodes->item(2)->attributes->getNamedItem('title')->textContent);
            $category = $this->clean($torrent->childNodes->item(4)->textContent);
            $title = $this->clean($torrent->childNodes->item(6)->textContent);
            $torrent_id = (int) $this->clean($links->item($i)->attributes->getNamedItem('data-topic_id')->textContent);
            $detail_url = "http://rutracker.org/forum/viewtopic.php?t=$torrent_id";

            $author_url = parse_url($torrent->childNodes->item(8)->firstChild->firstChild->attributes->getNamedItem('href')->textContent);
            $author_id = explode('=',$author_url['query']);
            $author_id = $author_id[1];
            $author = array(
                "id" => (int) $author_id,
                "name" => $this->clean($torrent->childNodes->item(8)->firstChild->firstChild->textContent)
            );
            $download_url = $this->clean($torrent->childNodes->item(10)->childNodes->item(3)->attributes->getNamedItem('href')->textContent);
            $size = $this->clean(str_replace(' ↓','',$torrent->childNodes->item(10)->childNodes->item(3)->textContent));
            $seeders = (int) $this->clean($torrent->childNodes->item(12)->firstChild->textContent);
            $leachers = (int) $this->clean($torrent->childNodes->item(14)->textContent);
            $downloads = (int) $this->clean($torrent->childNodes->item(16)->textContent);
            $added = date('d-m-Y',$this->clean($torrent->childNodes->item(18)->childNodes->item(1)->textContent));


            $result[] = array(

                "id" => $torrent_id,
                "title" => $title,
                "category" => $category,
                "status" => $status,
                "detail_url" => $detail_url,
                "author" => $author,
                "size" => $size,
                "download_url" => $download_url,
                "seeders" => $seeders,
                "leachers" => $leachers,
                "downloads" => $downloads,
                "added" => $added
            );

        }

        return $result;

    }


    private function clean($str)
    {

        @$string = htmlentities($str, null, 'utf-8');
        @$string = str_replace("&nbsp;", "", $string);

        return trim($string);
    }

} 