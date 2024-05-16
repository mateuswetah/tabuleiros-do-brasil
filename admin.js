if (wp && wp.hooks) {
    function getTainacanItemEditionRedirect (itemEditionRedirect, itemObject, itemId) {

        if ( itemObject.collectionId == tabuleirosbr_theme.jogos_collection_id || itemObject.collectionId == tabuleirosbr_theme.designers_collection_id) {
            return tabuleirosbr_theme.edit_admin_url + '?post_type=tnc_col_' + tabuleirosbr_theme.jogos_collection_id + '_item';
        } else if ( itemObject.collectionId( )) {
            return tabuleirosbr_theme.edit_admin_url + '?post_type=tnc_col_' + itemObject.collectionId + '_item';
        }

        return itemEditionRedirect;
    }
    wp.hooks.addFilter('tainacan_item_edition_after_update_redirect', 'tainacan-hooks', getTainacanItemEditionRedirect);
}