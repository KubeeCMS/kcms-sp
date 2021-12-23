<?php

namespace FSPoster\App\Providers;

class CatWalker extends \Walker
{
	private $data = [];
	private $taxonomies;
	private $response = [];

	private function __construct(){
		$this->taxonomies = get_taxonomies( ['public' => TRUE] );

		foreach ($this->taxonomies as $taxonomy){
			$this->response[$taxonomy] = [
				'text' => get_taxonomy($taxonomy)->label,
				'children' => []
			];
		}
	}

	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);

	public function start_el ( &$output, $object, $depth = 0, $args = [], $current_object_id = 0 )
	{
		$info = [
			'text'         => str_repeat("-", $depth) . $object->name,
			'id'           => $object->term_id,
			'taxonomy'     => $object->taxonomy
		];

		$this->data[] = $info;
		//parent::start_el( $output, $object, $depth, $args, $current_object_id ); // TODO: Change the autogenerated stub
	}

	public static function get_cats($query = NULL){
		$walker = new self();

		$args = [
			'orderby'       => 'count',
			'order'         => 'DESC',
			'hide_empty'    => 0,
			//'child_of'      => 0,
			//'hierarchical'  => 1,
			'depth'         => 3,
			//'taxonomy'      => $walker->taxonomies,
			'hide_if_empty' => FALSE
		];

		if(!empty($query)){
			$args['name__like'] = $query;
		}else{
			$args['number'] = 50;
		}

        if ( version_compare( get_bloginfo( 'version' ) , '4.5.0' ,  '>=' ) )
        {
            $terms = get_terms( $args );
        }
        else
        {
            $terms = get_terms( $walker->taxonomies,$args );
        }

		//makes hierarchy
		$walker->walk($terms, 10);

		foreach ($walker->data as $item)
        {
			$walker->response[$item['taxonomy']]['children'][] = [
				'text' => $item['text'],
				'id' => $item['id']
			];
		}

		return array_values($walker->response);
	}
}