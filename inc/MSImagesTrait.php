<?php
trait MSImages
{
    /**
     * https://wordpress.stackexchange.com/questions/107346/download-an-image-from-a-webpage-to-the-default-uploads-folder
     *
     * @param [type] $image_url
     * @param [type] $parent_id
     */
    public static function uploadRemoteImageAndAttach($image_url, $product_id, $filename = 'image.jpg')
    {

        $uploads_dir = wp_upload_dir();
        $post_name = get_post_field('post_name', $product_id);
        $filename_data = wp_check_filetype($filename);
        $filename = $post_name . '.' . $filename_data['ext'];
        $filename = sanitize_file_name($filename);
        $filename = wp_unique_filename($uploads_dir['path'], $filename);

        $header_array = [
            'Authorization' => 'Basic ' . base64_encode(get_option('woomss_login') . ':' . get_option('woomss_pass')),
        ];

        $args = [
            'headers'  => $header_array,
        ];

        $get = wp_remote_get($image_url, $args);

        if (empty($get['response']['code'])) {
            return false;
        }

        if (403 == $get['response']['code']) {
            $http_response = $get['http_response'];

            if ($http_response->get_status() == 403) {
                $response = $http_response->get_response_object();
                $url_image = $http_response->get_response_object()->url;

                $get2 = wp_remote_get($url_image);
                $mirror = wp_upload_bits($filename, '', wp_remote_retrieve_body($get2));
            }
        } else {

            $mirror = wp_upload_bits($filename, '', wp_remote_retrieve_body($get));
        }

        $type = $filename_data['type'];

        if (!$type)
            return false;


        $attachment = array(
            'post_title' => $filename,
            'post_mime_type' => $type
        );

        $attach_id = wp_insert_attachment($attachment, $mirror['file'], $product_id);

        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attach_data = wp_generate_attachment_metadata($attach_id, $mirror['file']);

        var_dump($attach_data);

        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}