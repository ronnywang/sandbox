<?php

class GithubSyncer
{
    protected static $_username = null;
    protected static $_password = null;
    protected static $_otp = null;
    protected static $_oauth_token = null;

    protected static function http($url, &$headers, $post_params = null, $custom_method = null)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'GitHub Map+ http://github.ronny.tw');
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        if (!is_null(self::$_username)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, self::$_username . ':' . self::$_password);
        } else if (!is_null(self::$_oauth_token)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, self::$_oauth_token . ':x-oauth-basic');
        }
        if (!is_null(self::$_otp)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-GitHub-OTP: ' . self::$_otp));
        }
        if (!is_null($post_params)) {
            if (is_array($post_params)) {
                $terms = array();
                foreach ($post_params as $k => $v) {
                    $terms[] = urlencode($k) . '=' . urlencode($v);
                }
                $post_fields = implode('&', $terms);
            } else {
                $post_fields = $post_params;
            }
            if (is_null($custom_method)) {
                curl_setopt($curl, CURLOPT_POST, true);
            } else {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($custom_method));
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
        }
        curl_setopt($curl, CURLOPT_HEADER, true);
        $content = curl_exec($curl);
        while (true) {
            list($header, $body) = explode("\r\n\r\n", $content, 2);
            if ('HTTP/1.1 100 Continue' != trim($header)) {
                break;
            }
            $content = $body;
        }
        $header_lines = explode("\n", $header);
        array_shift($header_lines); // remove http code
        $headers = array();
        foreach ($header_lines as $line){
            list($key, $value) = explode(": ", $line, 2);
            $headers[$key] = trim($value);
        }

        if ($headers['Status'] == '401 Unauthorized' and array_key_exists('X-GitHub-OTP', $headers)) {
            echo "OTP: ";
            self::$_otp = fgets(STDIN);
            return self::http($url, $headers);
        }

        error_log('Remains: ' . $headers['X-RateLimit-Remaining']);
        curl_close($curl);
        return $body;
    }

    protected static function pressAnyKey()
    {
        echo "Press Any Key to continue...\n"; 
        $fh = fopen("php://stdin", "r"); 
        $a = fgets( $fh); 
        fclose($fh); 
    }

    public static function getSHAsFromURL($url)
    {
        $shas = array();

        $ret = json_decode(self::http($url, $headers));
        foreach ($ret as $file){
            if ($file->type == 'dir') {
                // TODO: recursive
            } else {
                $shas[$file->name] = $file->sha;
            }
        }

        if ($link = self::getLinkFromHeaders($headers, 'next')){
            return array_merge($shas, self::getSHAsFromURL($link));
        }

        return $shas;
    }

    public static function getFilesFromURL($url)
    {
        $ret = json_decode(self::http($url, $headers));
        foreach ($ret as $file){
            if ($file->type == 'dir') {
                echo "{$file->path}/\n";
            } else {
                echo "{$file->path}\n";
            }
        }

        if ($link = self::getLinkFromHeaders($headers, 'next')){
            self::pressAnyKey();
            self::getFilesFromURL($link);
        }
    }

    public static function getLinkFromHeaders($headers, $rel)
    {
        //Link: <https://api.github.com/user/xxx/repos?page=2>; rel="next", <https://api.github.com/user/xxx/repos?page=2>; rel="last"
        $link_str = $headers['Link'];
        $terms = explode(", ", $link_str);
        foreach ($terms as $term) {
            if (!preg_match('/<([^>]*)>; rel="([^"]*)"/', $term, $matches)){
                continue;
            }
            if ($matches[2] == $rel) {
                return $matches[1];
            }
        }

        return null;
    }

    public static function getRepositoriesFromURL($url)
    {
        $ret = json_decode(self::http($url, $headers));
        foreach ($ret as $repo){
            echo "{$repo->full_name}\n";
        }

        if ($link = self::getLinkFromHeaders($headers, 'next')){
            self::pressAnyKey();
            self::getRepositoriesFromURL($link);
        }
    }

    public static function createBlob($user, $repository, $content)
    {
        $url = 'https://api.github.com/repos/' . urlencode($user) . '/' . urlencode($repository) . '/git/blobs';
        $ret = (self::http($url, $headers, json_encode(array(
            'content' => base64_encode($content),
            'encoding' => 'base64',
        ))));
        return json_decode($ret)->sha;
    }

    public static function getCommitObject($user, $repository, $ref)
    {
        $url = 'https://api.github.com/repos/' . urlencode($user) . '/' . urlencode($repository) . '/git/refs/' . urlencode($ref);
        $ret = json_decode(self::http($url, $headers));
        return $ret->object;
    }

    public static function command_sync($params, $options)
    {
        $source = array_shift($params);
        $target = array_shift($params);

        $remote_path = $local_path = null;
        $way = null;
        if (0 === strpos($source, 'github://')) {
            $remote_path = substr($source, 9);
            $way = 'download';
        } else {
            if (!file_exists($source) or !is_dir($source)) {
                die("'{$source}' is not found or is not directory\n");
            }
            $local_path = $source;
            $way = 'upload';
        }

        if (0 === strpos($target, 'github://')) {
            if (!is_null($remote_path)) {
                self::help('sync');
                return;
            }
            $remote_path = substr($target, 9);
        } else {
            if (!is_null($local_path)) {
                self::help('sync');
                return;
            }
            if (!file_exists($target) or !is_dir($target)) {
                die("'{$target}' is not found or is not directory\n");
            }
            $local_path = $target;
        }

        // count local file sha
        $local_shas = array();
        $local_path = rtrim($local_path, '/') . '/';
        $d = opendir($local_path);
        while ($f = readdir($d)) {
            if (is_dir($local_path . $f)) {
                continue;
            }
            if (preg_match('/\.swp$/', $f)) {
                continue;
            }
            $local_shas[$f] = self::countSHA($local_path . $f);
        }

        // count remote file sha
        list($user, $repository, $path) = explode('/', $remote_path, 3);
        $url = 'https://api.github.com/repos/' . urlencode($user) . '/' . urlencode($repository) . '/contents/' . trim($path, '/');
        $remote_shas = self::getSHAsFromURL($url);

        $downloads = $uploads = array();
        foreach ($remote_shas as $file => $remote_sha) {
            if (!array_key_exists($file, $local_shas)) {
                $downloads[] = $file;
                unset($local_shas[$file]);
            } elseif ($local_shas[$file] != $remote_sha) {
                $downloads[] = $file;
            } else {
                unset($local_shas[$file]);
            }
        }

        $uploads = array_keys($local_shas);

        if ($way == 'upload') {
            // TODO: delete = true ?
            // 1. get the current commit object
            $object = self::getCommitObject($user, $repository, 'heads/master');
            if (!$object->sha) {
                die("master branch not found");
            }

            // 2. retrieve the tree it points to
            $tree_sha = $commit_id = $object->sha;
            $path_terms = explode('/', trim($path, '/'));
            while ($term = array_pop($path_terms)) {
                $tree_url = "https://api.github.com/repos/{$user}/{$repository}/git/trees/{$tree_sha}";
                $obj = json_decode(self::http($tree_url, $headers));
                foreach ($obj->tree as $tree_obj) {
                    if ($tree_obj->path == $term) {
                        $tree_sha = $tree_obj->sha;
                        continue 2;
                    }
                }
                array_push($path_terms, $term);
                break;
            }

            // 3. create all blob
            $uploaded = array();
            foreach ($uploads as $upload_file) {
                $content = file_get_contents($local_path . '/' . $upload_file);
                $sha = self::createBlob($user, $repository, $content);
                $uploaded[$upload_file] = $sha;
            }

            // 4. create all tree
            $trees = array();
            foreach ($uploaded as $filename => $sha) {
                $tree = array(
                    'path' => implode('/', $path_terms) . '/' . $filename,
                    'mode' => '100644',
                    'type' => 'blob',
                    'sha' => $sha,
                );
                $trees[] = $tree;
            }
            $url = "https://api.github.com/repos/{$user}/{$repository}/git/trees";
            $ret = json_decode(self::http($url, $headers, json_encode(array(
                'base_tree' => $tree_sha,
                'tree' => $trees,
            ))));
            $commit_tree = $ret->sha;

            // 5. create a commit
            $url = "https://api.github.com/repos/{$user}/{$repository}/git/commits";
            $ret = json_decode(self::http($url, $headers, json_encode(array(
                'message' => 'Sync by github-syncer',
                'tree' => $commit_tree,
                'parents' => array($commit_id),
            ))));
            $commit_id = $ret->sha;

            // 6. update refs
            $url = "https://api.github.com/repos/{$user}/{$repository}/git/refs/heads/master";
            $ret = self::http($url, $headers, json_encode(array(
                'sha' => $commit_id,
            )), 'PATCH');
            var_dump($ret);
        } else {
        }
        error_log('download: ' . implode(', ', $downloads));
        error_log('upload: ' . implode(', ', $uploads));
    }

    public static function command_ls($params, $options)
    {
        $path = array_shift($params);
        list($user, $repository, $path) = explode('/', $path, 3);

        if (!$user) {
            self::help('ls');
            exit;
        }

        if (!$repository) {
            $url = 'https://api.github.com/users/' . urlencode($user) . '/repos';
            self::getRepositoriesFromURL($url);
            exit;
        }

        $url = 'https://api.github.com/repos/' . urlencode($user) . '/' . urlencode($repository) . '/contents/' . trim($path, '/');
        self::getFilesFromURL($url);
    }

    public static function help()
    {
        echo ("Usage: github-syncer SOURCE TARGET [-m COMMIT-MESSAGE]\n");
        echo (" TARGET: USERNAME/REPOSITORY/PATH \n");
    }

    protected static function countSHA($file)
    {
        return sha1('blob ' . filesize($file) . "\0" . file_get_contents($file));
    }

    public static function main($argv)
    {
        $options = array();
        $params = array();

        array_shift($argv);

        while (count($argv)){
            $term = array_shift($argv);
            if ($term[0] === '-') {
                $k = trim($term, '-');
                if (!array_key_exists($k, $options)){
                    $options[$k] = array();
                }
                $options[$k][] = array_shift($argv);
            } else { 
                $params[] = $term;
            }
        }

        if (array_key_exists('u', $options)) {
            echo "Password: ";
            system('stty -echo');
            $password = trim(fgets(STDIN));
            system('stty echo');

            self::$_username = $options['u'][0];
            self::$_password = $password;
        }

        if (array_key_exists('t', $options)) {
            self::$_oauth_token = $options['t'][0];
        }

        $command = array_shift($params);
        switch ($command) {
        case 'ls':
            self::command_ls($params, $options);
            break;

        case 'sync':
            self::command_sync($params, $options);
            break;

        case 'help':
        default:
            self::help($command);
        }
    }
}
