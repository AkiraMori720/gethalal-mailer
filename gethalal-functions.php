<?php


function gm_lang_object_ids($object_id, $type) {
    $current_language= apply_filters( 'wpml_current_language', NULL );
    if(class_exists( 'SitePress' )){
        if( is_array( $object_id ) ){
            $translated_object_ids = array();
            foreach ( $object_id as $id ) {
                $translated_object_ids[] = apply_filters( 'wpml_object_id', $id, $type, true, $current_language );
            }
            return $translated_object_ids;
        } else {
            return apply_filters( 'wpml_object_id', $object_id, $type, true, $current_language );
        }
    } else {
        return $object_id;
    }
}

