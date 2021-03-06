<?php defined('SYSPATH') or die('No direct script access.');
/**
 * description...
 *
 * @author		Chema <chema@open-classifieds.com>
 * @package		OC
 * @copyright	(c) 2009-2013 Open Classifieds Team
 * @license		GPL v3
 * *
 */
class Model_Location extends ORM {

	/**
	 * Table name to use
	 *
	 * @access	protected
	 * @var		string	$_table_name default [singular model name]
	 */
	protected $_table_name = 'locations';

	/**
	 * Column to use as primary key
	 *
	 * @access	protected
	 * @var		string	$_primary_key default [id]
	 */
	protected $_primary_key = 'id_location';


    protected $_belongs_to = array(
        'parent'   => array('model'       => 'Location',
                            'foreign_key' => 'id_location_parent'),
    );

    
    /**
     * global Model Location instance get from controller so we can access from anywhere like Model_Location::current()
     * @var Model_Location
     */
    protected static $_current = NULL;

    /**
     * returns the current location
     * @return Model_Location
     */
    public static function current()
    {
        //we don't have so let's retrieve
        if (self::$_current === NULL)
        {
            self::$_current = new self();
            if(Request::current()->param('location') != NULL || Request::current()->param('location') != URL::title(__('all')))
            {
                self::$_current = self::$_current->where('seoname', '=', Request::current()->param('location'))
                                                    ->limit(1)->cached()->find();
            }
        }

        return self::$_current;
    }
    
    /**
     * creates a location by name
     * @param  string  $name               
     * @param  integer $id_location_parent 
     * @param  string  $description        
     * @return Model_Location                      
     */
    public static function create_name($name,$order=0, $id_location_parent = 1, $parent_deep=0, $description = NULL)
    {
        $loc = new self();
        $loc->where('name','=',$name)->limit(1)->find();

        //if doesnt exists create
        if (!$loc->loaded())
        {
            $loc->name        = $name;
            $loc->seoname     = $loc->gen_seoname($name);
            $loc->id_location_parent = $id_location_parent;
            $loc->order       = $order;
            $loc->parent_deep = $parent_deep;
            $loc->description = $description;

            try
            {
                $loc->save();
            }
            catch (ORM_Validation_Exception $e)
            {
                throw HTTP_Exception::factory(500,$e->getMessage());
            }
        }

        return $loc;
    }

	/**
	 * Rule definitions for validation
	 *
	 * @return array
	 */
	public function rules()
	{
		return array(
				        'id_location'		=> array(array('numeric')),
				        'name'				=> array(array('not_empty'), array('max_length', array(':value', 64)), ),
				        'id_location_parent'=> array(),
				        'parent_deep'		=> array(),
				        'seoname'			=> array(array('not_empty'), array('max_length', array(':value', 145)), ),
				        'description'		=> array(),
				        'last_modified'		=> array(),
				        'has_images'			=> array(array('numeric')),
		);
	}

	/**
	 * Label definitions for validation
	 *
	 * @return array
	 */
	public function labels()
	{
		return  array(
	        'id_location'			=> 'Id',
	        'name'					=> __('Name'),
	        'id_location_parent'	=> __('Parent'),
	        'parent_deep'			=> __('Parent deep'),
	        'seoname'				=> __('Seoname'),
	        'description'			=> __('Description'),
	        'last_modified'			=> __('Last modified'),
	        'has_image'				=> __('Has image'),
		);
	}

    /**
     * Filters to run when data is set in this model. The password filter
     * automatically hashes the password when it's set in the model.
     *
     * @return array Filters
     */
    public function filters()
    {
        return array(
                'seoname' => array(
                                array(array($this, 'gen_seoname'))
                              ),
                'id_location_parent' => array(
                                array(array($this, 'check_parent'))
                              ),
        );
    }


    /**
     * we get the locations in an array 
     * @return array 
     */
    public static function get_as_array()
    {
        if ( ($locs_arr = Core::cache('locs_arr'))===NULL)
        {
            $locs = new self;
            $locs = $locs->order_by('order','asc')->find_all()->cached()->as_array('id_location');

            //transform the locs to an array
            $locs_arr = array();
            foreach ($locs as $loc) 
            {
                $locs_arr[$loc->id_location] =  array('name'               => $loc->name,
                                                      'order'              => $loc->order,
                                                      'id_location_parent' => $loc->id_location_parent,
                                                      'parent_deep'        => $loc->parent_deep,
                                                      'seoname'            => $loc->seoname,
                                                      'id'                 => $loc->id_location,
                                                    );
            }
            Core::cache('locs_arr',$locs_arr);
        }

        return $locs_arr;
    }

    /**
     * we get the locations in an array using as key the deep they are, perfect fro chained selects
     * @return array 
     */
    public static function get_by_deep()
    {
        // array by parent deep, 
        // each parent deep is one array with locations of the same index
        if ( ($locs_parent_deep = Core::cache('locs_parent_deep'))===NULL)
        {
            $locs = new self;
            $locs = $locs->order_by('order','asc')->find_all()->cached()->as_array('id_location');

            // array by parent deep, 
            // each parent deep is one array with locations of the same index
            $locs_parent_deep = array();
            foreach ($locs as $loc) 
            {
                $locs_parent_deep[$loc->parent_deep][$loc->id_location] =  array('name'               => $loc->name,
                                                                                  'id_location_parent' => $loc->id_location_parent,
                                                                                  'parent_deep'        => $loc->parent_deep,
                                                                                  'seoname'            => $loc->seoname,
                                                                                  'id'                 => $loc->id_location,
                                                                        );
            }
            //sort by key, in case lover level is befor higher
            ksort($locs_parent_deep);
            Core::cache('locs_parent_deep',$locs_parent_deep);
        }

        return $locs_parent_deep;
    }


    /**
     * we get the locations in an array miltidimensional by deep.
     * @return array 
     */
    public static function get_multidimensional()
    {
        if ( ($locs_m = Core::cache('locs_m'))===NULL)
        {
            $locs = new self;
            $locs = $locs->order_by('order','asc')->find_all()->cached()->as_array('id_location');

            //for each location we get his siblings
            $locs_s = array();
            foreach ($locs as $loc) 
                 $locs_s[$loc->id_location_parent][] = $loc->id_location;
                

            //last build multidimensional array
            if (count($locs_s)>1)
                $locs_m = self::multi_locs($locs_s);
            else
                $locs_m = array();
            Core::cache('locs_m',$locs_m);
        }
        return $locs_m;
    }

    /**
     * gets a multidimensional array wit the locations
     * @param  array  $locs_s      id_location->array(id_siblings)
     * @param  integer $id_location 
     * @param  integer $deep        
     * @return array               
     */
    public static function multi_locs($locs_s,$id_location = 1, $deep = 0)
    {    
        $ret = NULL;
        //we take all the siblings and try to set the grandsons...
        //we check that the id_location sibling has other siblings
        if (isset($locs_s[$id_location]))
        {
            foreach ($locs_s[$id_location] as $id_sibling) 
            {
                //we check that the id_location sibling has other siblings
                if (isset($locs_s[$id_sibling]))
                {
                    if (is_array($locs_s[$id_sibling]))
                    {
                        $ret[$id_sibling] = self::multi_locs($locs_s,$id_sibling,$deep+1);
                    }
                }
                //no siblings we only set the key
                else 
                    $ret[$id_sibling] = NULL;
                
            }
        }
        
        return $ret;
    }


    /**
     * we get the locations in an array and a multidimensional array to know the deep @todo refactor this, is a mess
     * @deprecated function not in use, just here so we do not break the API to old themes
     * @return array 
     */
    public static function get_all()
    {
        //as array
        $locs_arr = self::get_as_array();

        //multidimensional array
        $locs_m = self::get_multidimensional();

        //array by deep
        $locs_parent_deep = self::get_by_deep();
        
        return array($locs_arr,$locs_m, $locs_parent_deep);
    }

    /**
     * we get the locations in an array and a multidimensional array to know the deep
     * @param  int ID of location 
     * @param  string needed attribute to be returned
     * @return string   
     */
    
    public static function get_location($id, $attr)
    {
      $location = new self($id);
      return $location->$attr;
    }

    /**
   * counts the location ads
   * @return array
   */
    public static function get_location_count()
    {
        if ( ($locs_count = Core::cache('locs_count'))===NULL)
        {
            $expr_date = (is_numeric(core::config('advertisement.expire_date')))?core::config('advertisement.expire_date'):0;
            $db_prefix = Database::instance('default')->table_prefix();

            $locs = DB::select('l.*')
                  ->select(array(DB::select(DB::expr('COUNT("id_ad")'))
                          ->from(array('ads','a'))
                          ->where('a.id_location','=',DB::expr($db_prefix.'l.id_location'))
                          ->where(DB::expr('IF('.$expr_date.' <> 0, DATE_ADD( published, INTERVAL '.$expr_date.' DAY), DATE_ADD( NOW(), INTERVAL 1 DAY))'), '>', Date::unix2mysql())
                          ->where('a.status','=',Model_Ad::STATUS_PUBLISHED)
                          ->group_by('id_location'), 'count'))
                  ->from(array('locations', 'l'))
                  ->order_by('order','asc')
                  ->as_object()
                  ->cached()
                  ->execute();

            //array where we store the locations with the count
            $locs_count = array();

            //array to store parents_id with the count. So later we can easily add them up
            $parent_count = array();

            foreach ($locs as $l) 
            {
                $locs_count[$l->id_location] = array(   'id_location'         => $l->id_location,
                                                        'seoname'             => $l->seoname,
                                                        'name'                => $l->name,
                                                        'id_location_parent'  => $l->id_location_parent,
                                                        'parent_deep'         => $l->parent_deep,
                                                        'order'               => $l->order,
                                                        'has_siblings'        => FALSE,
                                                        'count'               => (is_numeric($l->count))?$l->count:0
                                                        );
                //counting the ads the parent have
                if ($l->id_location_parent!=0)
                {
                    if (!isset($parent_count[$l->id_location_parent]))
                        $parent_count[$l->id_location_parent] = 0;

                    $parent_count[$l->id_location_parent]+=$l->count;
                }
              
            }

            foreach ($parent_count as $id_location => $count) 
            {
                //attaching the count to the parents so we know each parent how many ads have
                $locs_count[$id_location]['count'] += $count;
                $locs_count[$id_location]['has_siblings'] = TRUE;
            }

            Core::cache('locs_count',$locs_count);
        }      
      
      return $locs_count;
    }


	public function form_setup($form)
	{
		$form->fields['description']['display_as'] = 'textarea';

        $form->fields['id_location_parent']['display_as']   = 'select';
        $form->fields['id_location_parent']['caption']      = 'name';   

        $form->fields['order']['display_as']   = 'select';
        $form->fields['order']['options']      = range(1, 100);

        // $form->fields['id_location_parent']['display_as'] = 'hidden';
        // $form->fields['parent_deep']['display_as'] = 'hidden';
        // $form->fields['order']['display_as'] = 'hidden';
	}

	public function exclude_fields()
	{
	  return array('created','parent_deep','has_image','last_modified');
	}

     /**
     * returns all the siblings ids+ the idlocation, used to filter the ads
     * @return array
     */
    public function get_siblings_ids()
    {
        if ($this->loaded())
        {
            //name used in the cache for storage
            $cache_name = 'get_siblings_ids_lcoations_'.$this->id_location;

            if ( ($ids_siblings = Core::cache($cache_name))===NULL)
            {
                //array that contains all the siblings as keys (1,2,3,4,..)
                $ids_siblings = array();

                //we add himself as we use the clause IN on the where
                $ids_siblings[] = $this->id_location;

                $locations = new self();
                $locations = $locations->where('id_location_parent','=',$this->id_location)->cached()->find_all();

                foreach ($locations as $location) 
                {
                    $ids_siblings[] = $location->id_location;

                    //adding his children recursevely if they have any
                    if ( count($siblings_locs = $location->get_siblings_ids())>1 ) 
                        $ids_siblings = array_merge($ids_siblings,$siblings_locs);       
                }

                //removing repeated values
                $ids_siblings = array_unique($ids_siblings);

                //cache the result is expensive!
                Core::cache($cache_name,$ids_siblings);
            }

            return $ids_siblings;
        }

        //not loaded
        return NULL;
    }

    /**
     * return the title formatted for the URL
     *
     * @param  string $title
     * 
     */
    public function gen_seoname($seoname)
    {
        //in case seoname is really small or null
        if (strlen($seoname)<3)
        {
            if (strlen($this->name)>=3)
                $seoname = $this->name;
            else
                $seoname = __('location').'-'.$seoname;
        }

        $seoname = URL::title($seoname);

        //this are reserved locations names used in the routes.php
        $banned_names = array('location',__('location'));
        //same name as a route..shit!
        if (in_array($seoname, $banned_names))
            $seoname = URL::title(__('location')).'-'.$seoname; 

        if ($seoname != $this->seoname)
        {
            $loc = new self;
            //find a user same seoname
            $s = $loc->where('seoname', '=', $seoname)->limit(1)->find();

            //found, increment the last digit of the seoname
            if ($s->loaded())
            {
                $cont = 2;
                $loop = TRUE;
                while($loop)
                {
                    $attempt = $seoname.'-'.$cont;
                    $loc = new self;
                    unset($s);
                    $s = $loc->where('seoname', '=', $attempt)->limit(1)->find();
                    if(!$s->loaded())
                    {
                        $loop = FALSE;
                        $seoname = $attempt;
                    }
                    else
                  {
                        $cont++;
                    }
                }
            }
        }

        return $seoname;
    }

    /**
     * returns the deep of parents of this location
     * @return integer
     */
    public function get_deep()
    {
        //initial deep
        $deep = 0;

        if ($this->loaded())
        {
            //getting all the cats as array
            $locs_arr = Model_Location::get_as_array();

            //getin the parent of this location
            $id_location_parent = $locs_arr[$this->id_location]['id_location_parent'];

            //counting till we find the begining
            while ($id_location_parent != 1 AND $id_location_parent != 0 AND $deep<100) 
            {
                $id_location_parent = $locs_arr[$id_location_parent]['id_location_parent'];
                $deep++;
            }
        }
        
        return $deep;
    }

    /**
     * rule to verify that we selected a parent if not put the root location
     * @param  integer $id_parent 
     * @return integer                     
     */
    public function check_parent($id_parent)
    {
        return (is_numeric($id_parent))? $id_parent:1;
    }

    /**
     * returns the url of the location icon
     * @return string url
     */
    public function get_icon()
    {
        if ($this->has_image) {
            if(core::config('image.aws_s3_active'))
            {
                $protocol = Core::is_HTTPS() ? 'https://' : 'http://';
                $version = $this->last_modified ? '?v='.Date::mysql2unix($this->last_modified) : NULL;
                
                return $protocol.core::config('image.aws_s3_domain').'images/locations/'.$this->seoname.'.png'.$version;
            }
            else
                return URL::base().'images/locations/'.$this->seoname.'.png'
                        .(($this->last_modified) ? '?v='.Date::mysql2unix($this->last_modified) : NULL);
        }
        
        return FALSE;
    }

    /**
     * deletes the icon of the category
     * @return boolean 
     */
    public function delete_icon()
    {
        if ( ! $this->_loaded)
            throw new Kohana_Exception('Cannot delete :model model because it is not loaded.', array(':model' => $this->_object_name));


        if (core::config('image.aws_s3_active'))
        {
            require_once Kohana::find_file('vendor', 'amazon-s3-php-class/S3','php');
            $s3 = new S3(core::config('image.aws_access_key'), core::config('image.aws_secret_key'));
        }

        $root = DOCROOT.'images/locations/'; //root folder
            
        if (!is_dir($root)) 
        {
            return FALSE;
        }
        else
        {   
            //delete icon
            @unlink($root.$this->seoname.'.png');
            
            // delete icon from Amazon S3
            if(core::config('image.aws_s3_active'))
                $s3->deleteObject(core::config('image.aws_s3_bucket'), 'images/locations/'.$this->seoname.'.png');
            
            // update category info
            $this->has_image = 0;
            $this->last_modified = Date::unix2mysql();
            $this->save();
            
        }

        return TRUE;
    }

    /**
     * Deletes a single record while ignoring relationships.
     *
     * @chainable
     * @throws Kohana_Exception
     * @return ORM
     */
    public function delete()
    {
        if ( ! $this->_loaded)
            throw new Kohana_Exception('Cannot delete :model model because it is not loaded.', array(':model' => $this->_object_name));

        //remove image
        $this->delete_icon();
        
        //delete subscribtions
        DB::delete('subscribers')->where('id_location', '=',$this->id_location)->execute();

        parent::delete();
    }

} // END Model_Location
