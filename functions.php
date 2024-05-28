<?php

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'tabuleirosbr-style', get_stylesheet_uri() );
});

// Valor padrão para a coleção de Jogos. Será definido no customizer.
const TABULEIROSBR_JOGOS_COLLECTION_ID = 68;//466;

// Valor padrão para a coleção de Designers. Será definido no customizer.
const TABULEIROSBR_DESIGNERS_COLLECTION_ID = 75;//267;

// Slug do papel de usuário "Gestor de Museu"
const TABULEIROSBR_GESTOR_DE_MUSEU_ROLE = 'tainacan-designer';

const TABULEIROSBR_DESINGER_NAME_METADATUM_ID = 78;

/**
 * Função utilitaria para obter o id da coleção Jogos
 */
function tabuleirosbr_get_jogos_collection_id() {
	return get_theme_mod( 'tabuleirosbr_jogos_collection', TABULEIROSBR_JOGOS_COLLECTION_ID );
}

/**
 * Função utilitaria para obter o id da coleção Designers
 */
function tabuleirosbr_get_designers_collection_id() {
	return get_theme_mod( 'tabuleirosbr_designers_collection', TABULEIROSBR_DESIGNERS_COLLECTION_ID );
}

/**
 * Função utilitaria para obter o tipo de post da coleção Jogos
 */
function tabuleirosbr_get_jogos_collection_post_type() {
	return 'tnc_col_' . tabuleirosbr_get_jogos_collection_id() . '_item';
}

/**
 * Função utilitaria para obter o tipo de post da coleção Jogos
 */
function tabuleirosbr_get_designers_collection_post_type() {
	return 'tnc_col_' . tabuleirosbr_get_designers_collection_id() . '_item';
}

/**
 * Função para checar se o usuário atual é um designer 
 */
function tabuleirosbr_user_is_designer( $user = NULL ) {

	if ( !isset($user) || $user === NULL )
		$user = wp_get_current_user();

	return in_array( 'tainacan-designer', isset($user->roles) ? $user->roles : [] );
}

/**
 * Altera o link de edição de posts da coleção dos jogos ou designers
 */
function tabuleirosbr_collection_edit_post_link( $url, $post_ID) {

	if ( get_post_type($post_ID) == tabuleirosbr_get_jogos_collection_post_type() )
		$url = admin_url( '?page=tainacan_admin#/collections/' . tabuleirosbr_get_jogos_collection_id() . '/items/' . $post_ID . '/edit' );
	else if ( get_post_type($post_ID) == tabuleirosbr_get_designers_collection_post_type() )
		$url = admin_url( '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/' . $post_ID . '/edit' );

    return $url;
}
add_filter( 'get_edit_post_link', 'tabuleirosbr_collection_edit_post_link', 10, 2 );

/**
 * Altera o link de criação de posts da coleção dos jogos ou designers
 */
function tabuleirosbr_collection_add_new_post( $url, $path) {

	if ( str_contains($path, "post-new.php") && str_contains( $path, 'post_type=' . tabuleirosbr_get_jogos_collection_post_type() ) )
		$url = admin_url( '?page=tainacan_admin#/collections/' . tabuleirosbr_get_jogos_collection_id() . '/items/new' );
	else if ( str_contains($path, "post-new.php") && str_contains( $path, 'post_type=' . tabuleirosbr_get_designers_collection_post_type() ) )
		$url = admin_url( '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/new' );
	
    return $url;
}
add_filter( 'admin_url', 'tabuleirosbr_collection_add_new_post', 10, 2 );

/**
 * Redireciona o usuário após o login para a paǵina de gestão dos jogos
 */
function tabuleirosbr_jogos_login_redirect($redirect_url, $request, $user) {

	if ( tabuleirosbr_user_is_designer($user) ) {
		$tainacan_items_repository = \Tainacan\Repositories\Items::get_instance();
		$user_designers = $tainacan_items_repository->fetch( array( 'author' => $user->ID, 'post_status' => 'any' ), tabuleirosbr_get_designers_collection_id(), 'OBJECT' );
		if ( count($user_designers) >= 1 )
			return admin_url( 'edit.php?post_type=tnc_col_' . tabuleirosbr_get_jogos_collection_id() . '_item' );
		else
			return admin_url(  '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/new' );
	}

	return $redirect_url;	
}	
add_filter('login_redirect', 'tabuleirosbr_jogos_login_redirect', 10, 3);

/** 
 * Registra estilo e scripts do lado admin
 */
function tabuleirosbr_admin_enqueue_styles() {
	wp_enqueue_style( 'tabuleirosbr-admin-style', get_stylesheet_directory_uri() . '/admin.css' );
	wp_enqueue_script( 'tabuleirosbr-admin-script', get_stylesheet_directory_uri() . '/admin.js', array('wp-hooks'), wp_get_theme()->get('Version') );
	
	wp_localize_script( 'tabuleirosbr-admin-script', 'tabuleirosbr_theme', array(
        'jogos_collection_id' => tabuleirosbr_get_jogos_collection_id(),
        'designers_collection_id' => tabuleirosbr_get_designers_collection_id(),
		'edit_admin_url' => admin_url( 'edit.php'),
    ) );
}
add_action( 'admin_enqueue_scripts', 'tabuleirosbr_admin_enqueue_styles' );

/**
 * Lista somente os jogos ou designers do usuário atual, se ele for designer
 */
function tabuleirosbr_pre_get_post( $query ) {
    if ( !is_admin() )
        return;

    if ( $query->is_main_query() && ( $query->query_vars['post_type'] == tabuleirosbr_get_jogos_collection_post_type() || $query->query_vars['post_type'] == tabuleirosbr_get_designers_collection_post_type() ) ) {
        if ( tabuleirosbr_user_is_designer() )
			$query->set( 'author', get_current_user_id() );
    }
}
add_action( 'pre_get_posts', 'tabuleirosbr_pre_get_post' );

/**
 * Adiciona classe css ao Admin do WordPress para estilizar a página que lista os jogos e designers
 */
function tabuleirosbr_custom_body_class($classes) {
	global $pagenow;

	if ( $pagenow == 'edit.php' && isset($_GET['post_type']) && ( $_GET['post_type'] === tabuleirosbr_get_jogos_collection_post_type() || $_GET['post_type'] === tabuleirosbr_get_designers_collection_post_type() ) )
        $classes .= ' post-type-tabuleirosbr';

	if ( tabuleirosbr_user_is_designer() )
		$classes .= ' user-is-designer';

    return $classes;
}
add_filter('admin_body_class', 'tabuleirosbr_custom_body_class');


/**
 * Altera o link de criação de posts da coleção dos jogos e designers no menu do admin
 */
function tabuleirosbr_collection_add_new_post_menu() {
	global $submenu;

	$jogos_collection_id = tabuleirosbr_get_jogos_collection_id();

	if ( isset($submenu['edit.php?post_type=tnc_col_' . $jogos_collection_id . '_item'][10]) && isset($submenu['edit.php?post_type=tnc_col_' . $jogos_collection_id . '_item'][10][2]) )
		$submenu['edit.php?post_type=tnc_col_' . $jogos_collection_id . '_item'][10][2] =  admin_url( '?page=tainacan_admin#/collections/' . $jogos_collection_id . '/items/new' );

	$designers_collection_id = tabuleirosbr_get_designers_collection_id();

	if ( isset($submenu['edit.php?post_type=tnc_col_' . $designers_collection_id . '_item'][10]) && isset($submenu['edit.php?post_type=tnc_col_' . $designers_collection_id . '_item'][10][2]) )
		$submenu['edit.php?post_type=tnc_col_' . $designers_collection_id . '_item'][10][2] =  admin_url( '?page=tainacan_admin#/collections/' . $designers_collection_id . '/items/new' );
	
}
add_filter( 'admin_menu', 'tabuleirosbr_collection_add_new_post_menu', 10);

/**
 * Inclui a coleção dos jogos e designers no menu admin
 */
function tabuleirosbr_list_collection_in_admin($args, $post_type){

    if ( $post_type == tabuleirosbr_get_jogos_collection_post_type() ) {
		$args['show_ui'] = true;
		$args['show_in_menu'] = true;
		$args['menu_icon'] = 'dashicons-editor-kitchensink';
		$args['menu_position'] = 3;
		$args['labels']['name'] = __( 'Jogos', 'tabuleirosbr ');// General name for the post type, usually plural. The same and overridden by $post_type_object->label. Default is ‘Posts’ / ‘Pages’.
		$args['labels']['singular_name'] = __( 'Jogo', 'tabuleirosbr ');// Name for one object of this post type. Default is ‘Post’ / ‘Page’.
		$args['labels']['add_new'] = __( 'Adicionar novo', 'tabuleirosbr ');// Label for adding a new item. Default is ‘Add New Post’ / ‘Add New Page’.
		$args['labels']['add_new_item'] = __( 'Adicionar novo jogo', 'tabuleirosbr ');// Label for adding a new singular item. Default is ‘Add New Post’ / ‘Add New Page’.
		$args['labels']['edit_item'] = __( 'Editar jogo', 'tabuleirosbr ');// Label for editing a singular item. Default is ‘Edit Post’ / ‘Edit Page’.
		$args['labels']['new_item'] = __( 'Novo jogo', 'tabuleirosbr ');// Label for the new item page title. Default is ‘New Post’ / ‘New Page’.
		$args['labels']['view_item'] = __( 'Ver jogo', 'tabuleirosbr ');// Label for viewing a singular item. Default is ‘View Post’ / ‘View Page’.
		$args['labels']['view_items'] = __( 'Ver jogos', 'tabuleirosbr ');// Label for viewing post type archives. Default is ‘View Posts’ / ‘View Pages’.
		$args['labels']['search_items'] = __( 'Pesquisar jogos', 'tabuleirosbr ');// Label for searching plural items. Default is ‘Search Posts’ / ‘Search Pages’.
		$args['labels']['not_found'] = __( 'Nenhum jogo encontrado', 'tabuleirosbr ');// Label used when no items are found. Default is ‘No posts found’ / ‘No pages found’.
		$args['labels']['not_found_in_trash'] = __( 'Nenhum jogo na lixeira', 'tabuleirosbr ');// Label used when no items are in the Trash. Default is ‘No posts found in Trash’ / ‘No pages found in Trash’.
		$args['labels']['all_items'] = __( 'Todas os jogos', 'tabuleirosbr ');// Label to signify all items in a submenu link. Default is ‘All Posts’ / ‘All Pages’.
		$args['labels']['archives'] = __( 'Lista de jogos', 'tabuleirosbr ');// Label for archives in nav menus. Default is ‘Post Archives’ / ‘Page Archives’.
		$args['labels']['attributes'] = __( 'Dados dos jogos', 'tabuleirosbr ');// Label for the attributes meta box. Default is ‘Post Attributes’ / ‘Page Attributes’.
		$args['labels']['insert_into_item'] = __( 'Inserir no jogo', 'tabuleirosbr ');// Label for the media frame button. Default is ‘Insert into post’ / ‘Insert into page’.
		$args['labels']['uploaded_to_this_item'] = __( 'Enviado para esse jogo', 'tabuleirosbr ');// Label for the media frame filter. Default is ‘Uploaded to this post’ / ‘Uploaded to this page’.
		$args['labels']['filter_items_list'] = __( 'Filtrar lista de jogos', 'tabuleirosbr ');// Label for the table views hidden heading. Default is ‘Filter posts list’ / ‘Filter pages list’.
		$args['labels']['items_list_navigation'] = __( 'Navegação na lista de jogos', 'tabuleirosbr ');// Label for the table pagination hidden heading. Default is ‘Posts list navigation’ / ‘Pages list navigation’.
		$args['labels']['items_list'] = __( 'Lista de jogos', 'tabuleirosbr ');// Label for the table hidden heading. Default is ‘Posts list’ / ‘Pages list’.
		$args['labels']['item_published'] = __( 'Jogo publicado', 'tabuleirosbr ');// Label used when an item is published. Default is ‘Post published.’ / ‘Page published.’
		$args['labels']['item_published_privately'] = __( 'Jogo publicado de forma privada', 'tabuleirosbr ');// Label used when an item is published with private visibility. Default is ‘Post published privately.’ / ‘Page published privately.’
		$args['labels']['item_reverted_to_draft'] = __( 'Jogo mantido como rascunho', 'tabuleirosbr ');// Label used when an item is switched to a draft. Default is ‘Post reverted to draft.’ / ‘Page reverted to draft.’
		$args['labels']['item_trashed'] = __( 'Jogo no lixeira', 'tabuleirosbr ');// Label used when an item is moved to Trash. Default is ‘Post trashed.’ / ‘Page trashed.’
		$args['labels']['item_scheduled'] = __( 'Jogo agendado', 'tabuleirosbr ');// Label used when an item is scheduled for publishing. Default is ‘Post scheduled.’ / ‘Page scheduled.’
		$args['labels']['item_updated'] = __( 'Jogo atualizado', 'tabuleirosbr ');// Label used when an item is updated. Default is ‘Post updated.’ / ‘Page updated.’
		$args['labels']['item_link'] = __( 'Link do jogo', 'tabuleirosbr ');// Title for a navigation link block variation. Default is ‘Post Link’ / ‘Page Link’.
		$args['labels']['item_link_description'] = __( 'Um link para um jogo', 'tabuleirosbr ');// Description for a navigation link block variation. Default is ‘A link to a post.’ / ‘A link to a page.’
    } else if ( $post_type == tabuleirosbr_get_designers_collection_post_type() ) {
		$args['show_ui'] = true;
		$args['show_in_menu'] = true;
		$args['menu_icon'] = 'dashicons-groups';
		$args['menu_position'] = 3;
		$args['labels']['name'] = __( 'Designers', 'tabuleirosbr ');// General name for the post type, usually plural. The same and overridden by $post_type_object->label. Default is ‘Posts’ / ‘Pages’.
		$args['labels']['singular_name'] = __( 'Designer', 'tabuleirosbr ');// Name for one object of this post type. Default is ‘Post’ / ‘Page’.
		$args['labels']['add_new'] = __( 'Adicionar novo', 'tabuleirosbr ');// Label for adding a new item. Default is ‘Add New Post’ / ‘Add New Page’.
		$args['labels']['add_new_item'] = __( 'Adicionar novo designer', 'tabuleirosbr ');// Label for adding a new singular item. Default is ‘Add New Post’ / ‘Add New Page’.
		$args['labels']['edit_item'] = __( 'Editar designer', 'tabuleirosbr ');// Label for editing a singular item. Default is ‘Edit Post’ / ‘Edit Page’.
		$args['labels']['new_item'] = __( 'Novo designer', 'tabuleirosbr ');// Label for the new item page title. Default is ‘New Post’ / ‘New Page’.
		$args['labels']['view_item'] = __( 'Ver designer', 'tabuleirosbr ');// Label for viewing a singular item. Default is ‘View Post’ / ‘View Page’.
		$args['labels']['view_items'] = __( 'Ver designers', 'tabuleirosbr ');// Label for viewing post type archives. Default is ‘View Posts’ / ‘View Pages’.
		$args['labels']['search_items'] = __( 'Pesquisar designers', 'tabuleirosbr ');// Label for searching plural items. Default is ‘Search Posts’ / ‘Search Pages’.
		$args['labels']['not_found'] = __( 'Nenhum designer encontrado', 'tabuleirosbr ');// Label used when no items are found. Default is ‘No posts found’ / ‘No pages found’.
		$args['labels']['not_found_in_trash'] = __( 'Nenhum designer na lixeira', 'tabuleirosbr ');// Label used when no items are in the Trash. Default is ‘No posts found in Trash’ / ‘No pages found in Trash’.
		$args['labels']['all_items'] = __( 'Todas os designers', 'tabuleirosbr ');// Label to signify all items in a submenu link. Default is ‘All Posts’ / ‘All Pages’.
		$args['labels']['archives'] = __( 'Lista de designers', 'tabuleirosbr ');// Label for archives in nav menus. Default is ‘Post Archives’ / ‘Page Archives’.
		$args['labels']['attributes'] = __( 'Dados dos designers', 'tabuleirosbr ');// Label for the attributes meta box. Default is ‘Post Attributes’ / ‘Page Attributes’.
		$args['labels']['insert_into_item'] = __( 'Inserir no designer', 'tabuleirosbr ');// Label for the media frame button. Default is ‘Insert into post’ / ‘Insert into page’.
		$args['labels']['uploaded_to_this_item'] = __( 'Enviado para esse designer', 'tabuleirosbr ');// Label for the media frame filter. Default is ‘Uploaded to this post’ / ‘Uploaded to this page’.
		$args['labels']['filter_items_list'] = __( 'Filtrar lista de designers', 'tabuleirosbr ');// Label for the table views hidden heading. Default is ‘Filter posts list’ / ‘Filter pages list’.
		$args['labels']['items_list_navigation'] = __( 'Navegação na lista de designers', 'tabuleirosbr ');// Label for the table pagination hidden heading. Default is ‘Posts list navigation’ / ‘Pages list navigation’.
		$args['labels']['items_list'] = __( 'Lista de designers', 'tabuleirosbr ');// Label for the table hidden heading. Default is ‘Posts list’ / ‘Pages list’.
		$args['labels']['item_published'] = __( 'Designer publicado', 'tabuleirosbr ');// Label used when an item is published. Default is ‘Post published.’ / ‘Page published.’
		$args['labels']['item_published_privately'] = __( 'Designer publicado de forma privada', 'tabuleirosbr ');// Label used when an item is published with private visibility. Default is ‘Post published privately.’ / ‘Page published privately.’
		$args['labels']['item_reverted_to_draft'] = __( 'Designer mantido como rascunho', 'tabuleirosbr ');// Label used when an item is switched to a draft. Default is ‘Post reverted to draft.’ / ‘Page reverted to draft.’
		$args['labels']['item_trashed'] = __( 'Designer no lixeira', 'tabuleirosbr ');// Label used when an item is moved to Trash. Default is ‘Post trashed.’ / ‘Page trashed.’
		$args['labels']['item_scheduled'] = __( 'Designer agendado', 'tabuleirosbr ');// Label used when an item is scheduled for publishing. Default is ‘Post scheduled.’ / ‘Page scheduled.’
		$args['labels']['item_updated'] = __( 'Designer atualizado', 'tabuleirosbr ');// Label used when an item is updated. Default is ‘Post updated.’ / ‘Page updated.’
		$args['labels']['item_link'] = __( 'Link do designer', 'tabuleirosbr ');// Title for a navigation link block variation. Default is ‘Post Link’ / ‘Page Link’.
		$args['labels']['item_link_description'] = __( 'Um link para um designer', 'tabuleirosbr ');// Description for a navigation link block variation. Default is ‘A link to a post.’ / ‘A link to a page.’
	}

    return $args;
}
add_filter('register_post_type_args', 'tabuleirosbr_list_collection_in_admin', 10, 2);


/** Adiciona mensagem de boas vindas ao título da lista de jogos ou designers */
function tabuleirosbr_add_welcome_message() {

	$current_screen = get_current_screen();
	
	if ( $current_screen && property_exists( $current_screen, 'parent_base' ) && property_exists( $current_screen, 'post_type' ) ) {
		
		$post_type = $current_screen->post_type;
		$parent_base = $current_screen->parent_base;

		if ( $parent_base === 'edit' && tabuleirosbr_get_jogos_collection_post_type() === $post_type ) : ?>
			<div class="tabuleirosbr-admin-welcome-panel">
				<h1>Boas vindas ao <img alt="Tabuleiros do Brasil" src="https://tabuleirosdobrasil.com/wp-content/uploads/2024/04/tabuleiros-do-brasil_completo-2048x321.png"></h1>
				<p class="subtitle">Um repositório digital de Jogos de Tabuleiros brasileiros.</p>
				<br>
				<p>
					<?php
					// Busca por itens da coleção designers para montar o menu
					$tainacan_items_repository = \Tainacan\Repositories\Items::get_instance();
					$designers_args = [
						'post_status' => 'any',
						'author' => get_current_user_id(),
					];
					$designers_items = $tainacan_items_repository->fetch($designers_args, tabuleirosbr_get_designers_collection_id(), 'OBJECT');

					if ( $designers_items && count($designers_items) >= 1 ) {
						_e('Você já cadastrou seus dados como designer. Agora você pode criar jogos e vinculá-los ao seu perfil.', 'tabuleirosbr');
					} else {
						echo 'Você ainda não cadastrou seus dados como designer. Preencha seus dados para poder vincular jogos ao seu perfil. <a href="' . admin_url( '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/new' ) . '">Cadastrar como designer</a>';
					}
					?>
				</p>
				<p>Se você quiser baixar o Termo de Consentimento Livre e Esclarecido, está <a href="https://drive.google.com/file/d/1Bnn31pMxrBpn0y6C8hFH8mSok7mk9dZy/view" target="_blank">aqui</a>.</p>
			</div>
		<?php elseif ( $parent_base === 'edit' && tabuleirosbr_get_designers_collection_post_type() === $post_type ) : ?>
			<div class="tabuleirosbr-admin-welcome-panel">
				<h1>Boas vindas ao <img alt="Tabuleiros do Brasil" src="https://tabuleirosdobrasil.com/wp-content/uploads/2024/04/tabuleiros-do-brasil_completo-2048x321.png"></h1>
				<p class="subtitle">Um repositório digital de Jogos de Tabuleiros brasileiros.</p>
				<br>
				<p>
					Preencha seus dados como designer para poder vincular jogos ao seu perfil.
				</p>
				<p>Se você quiser baixar o Termo de Consentimento Livre e Esclarecido, está <a href="https://drive.google.com/file/d/1Bnn31pMxrBpn0y6C8hFH8mSok7mk9dZy/view" target="_blank">aqui</a>.</p>
			</div>
		<?php endif;
	}
}
add_action( 'admin_notices', 'tabuleirosbr_add_welcome_message', 10 );

/*
 * Adiciona parâmetros para o Admin Tainacan para esconder elementos que não são necessários
 */
function tabuleirosbr_set_tainacan_admin_options($options) {
	
	if ( tabuleirosbr_user_is_designer() ) {
		$options['hideTainacanHeader'] = true;
		$options['hidePrimaryMenu'] = true;
		$options['hideRepositorySubheader'] = true;
		$options['hideCollectionSubheader'] = true;
		$options['hideItemEditionCollectionName'] = true;
		$options['hideItemEditionCommentsToggle'] = true;
		$options['hideItemEditionCollapses'] = true;
		$options['hideItemEditionMetadataTypes'] = true;
		$options['hideItemSingleExposers'] = true;
		$options['hideItemSingleActivities'] = true;
	}
	return $options;
};
add_filter('tainacan-admin-ui-options', 'tabuleirosbr_set_tainacan_admin_options');

/**
 * Adiciona coleção de jogos e link para designer no admin
 */
function tabuleirosbr_add_collections_to_toolbar($admin_bar) {
	
	// Busca por itens da coleção designers para montar o menu
	$tainacan_items_repository = \Tainacan\Repositories\Items::get_instance();
	$designers_args = [
		'posts_per_page'=> -1,
		'post_status' => 'any',
		'author' => get_current_user_id(),
	];
	$designers_items = $tainacan_items_repository->fetch($designers_args, tabuleirosbr_get_designers_collection_id(), 'WP_Query');
	$total_designers = $designers_items->found_posts;

	// Se houver apenas um designer, link direto pra ela
	if ( $total_designers >= 1) {

		while ( $designers_items->have_posts() ) {
			$designers_items->the_post();
			$admin_bar->add_menu( array(
				'id'    => 'designer-' . get_the_ID(),
				'title' => get_the_title(),
				'href'  => admin_url(  '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/' . get_the_ID() . '/edit' ),
				'meta'  => array(
					'title' => get_the_title()
				),
			));
			$admin_bar->add_menu( array(
				'id'    => 'designer-' . get_the_ID() . '-profile',
				'title' => __('Meus dados de designer', 'tabuleirosbr'),
				'href'  => admin_url(  '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/' . get_the_ID() . '/edit' ),
				'meta'  => array(
					'title' => __('Meus dados de designer', 'tabuleirosbr'),
				),
				'parent' => 'user-actions'
			));
		}

	} else {
		
		// Adiciona link para cadastrar dados de designer
		$admin_bar->add_menu( array(
			'id'    => 'novo-designer',
			'title' => __( 'Cadastrar-me como designer', 'tabuleirosbr' ),
			'href'  => admin_url(  '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/new' ),
			'meta'  => array(
				'title' => __( 'Cadastrar-me como designer', 'tabuleirosbr' ),        
			),
		));
		$admin_bar->add_menu( array(
			'id'    => 'novo-designer-profile',
			'title' => __( 'Cadastrar-me como designer', 'tabuleirosbr' ),
			'href'  => admin_url(  '?page=tainacan_admin#/collections/' . tabuleirosbr_get_designers_collection_id() . '/items/new' ),
			'meta'  => array(
				'title' => __( 'Cadastrar-me como designer', 'tabuleirosbr' ),        
			),
			'parent' => 'user-actions'
		));
	}
	
}
add_action('admin_bar_menu', 'tabuleirosbr_add_collections_to_toolbar', 100);


/**
 * Define automaticamente o nome do designer como autor do item
 */
function tabuleirosbr_preset_nome_designer($item) {
	if ( $item instanceof \Tainacan\Entities\Item ) {
		$collection_id = $item->get_collection_id();

	 	if ( $collection_id == tabuleirosbr_get_designers_collection_id() ) {

			$current_user = wp_get_current_user();

			if ( $current_user instanceof WP_User && tabuleirosbr_user_is_designer($current_user) ) {
				
				$name_metadatum = new \Tainacan\Entities\Metadatum( TABULEIROSBR_DESINGER_NAME_METADATUM_ID );

				if ( $name_metadatum instanceof \Tainacan\Entities\Metadatum ) {
					
					$new_name_item_metadatum = new \Tainacan\Entities\Item_Metadata_Entity( $item, $name_metadatum );
			
					if ( !$new_name_item_metadatum->has_value() ) {
						$new_name_item_metadatum->set_value( $current_user->display_name );
				
						if ( $new_name_item_metadatum->validate() ) {
							\Tainacan\Repositories\Item_Metadata::get_instance()->insert( $new_name_item_metadatum );
						}
					}
				}
			}
		}

	}
};
add_action('tainacan-insert', 'tabuleirosbr_preset_nome_designer', 10, 1);