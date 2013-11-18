<?php defined('SYSPATH') or die('No direct script access.');?>
<nav class="navbar navbar-inverse navbar-fixed-top bs-docs-nav col-md-12" role="navigation">

    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#mobile-menu-panel">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="<?=Route::url('default')?>"><?=core::config('general.site_name')?></a>
    </div>

	<?
    $cats = Model_Category::get_category_count();
    $loc_seoname = NULL;
    if (Controller::$location!==NULL)
    {
        if (Controller::$location->loaded())
            $loc_seoname = Controller::$location->seoname;
    }
    ?>
	<div class="collapse navbar-collapse" id="mobile-menu-panel">
		<ul class="nav navbar-nav">
		<?if ( count( $menus = Menu::get() )>0 ):?>
            <?foreach ($menus as $menu => $data):?>
                <li class="<?=(Request::current()->uri()==$data['url'])?'active':''?>" >
                <a href="<?=$data['url']?>" target="<?=$data['target']?>">
                    <?if($data['icon']!=''):?><i class="<?=$data['icon']?>"></i> <?endif?>
                    <?=$data['title']?></a> 
                </li>
            <?endforeach?>
        <?else:?>
			<?nav_link(__('Listing'),'ad', 'glyphicon glyphicon-list' ,'listing', 'list')?>
			<li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?=__('Categories')?> <b class="caret"></b></a>
                <ul class="dropdown-menu">

              	<?foreach($cats as $c ):?>
              		<?if($c['id_category_parent'] == 1 && $c['id_category'] != 1):?>
						<li class="dropdown-submenu">
                            <a tabindex="-1" title="<?=$c['seoname']?>" <?=(core::config('advertisement.parent_category'))?'href="'.Route::url('list', array('category'=>$c['seoname'],'location'=>$loc_seoname)).'"':""?>>
                                <?=$c['name']?></a>
                            
    							<ul class="dropdown-menu">							
    						 	<?foreach($cats as $chi):?>
                            	<?if($chi['id_category_parent'] == $c['id_category']):?>
                           			<li>
                                        <a title="<?=$chi['name']?>" href="<?=Route::url('list', array('category'=>$chi['seoname'],'location'=>$loc_seoname))?>">
                                            <span class="header_cat_list"><?=$chi['name']?></span> 
                                            <span class="count_ads"><span class="badge badge-success"><?=$chi['count']?></span></span>
                                        </a>
                                    </li>
                           		<?endif?>
                         		<?endforeach?>
    							</ul>
						</li>
					<?endif?>
				<?endforeach?>
              </ul>
            </li>
            <?if (core::config('general.blog')==1):?>
                <?nav_link(__('Blog'),'blog','','index','blog')?>
            <?endif?>
            <?nav_link('','ad', 'glyphicon glyphicon-search ', 'advanced_search', 'search')?>
            <?if (core::config('advertisement.map')==1):?>
                <?nav_link('','map', 'glyphicon glyphicon-globe ', 'index', 'map')?>
            <?endif?>
            <?nav_link('','contact', 'glyphicon glyphicon-envelope ', 'index', 'contact')?>
            <?nav_link('','rss', 'glyphicon glyphicon-signal ', 'index', 'rss')?>
        <?endif?>
        </ul>
        <?= FORM::open(Route::url('search'), array('class'=>'navbar-form navbar-left', 'method'=>'GET', 'action'=>''))?>
            <div class="form-group">
                <input type="text" name="search" class="search-query form-control" placeholder="<?=__('Search')?>">
            </div>  
        <?= FORM::close()?>
		
        <div class="btn-group pull-right btn-header-group">
            <?=View::factory('widget_login')?>
        
            <a class="btn btn-danger" href="<?=Route::url('post_new')?>">
                <i class="glyphicon glyphicon-pencil glyphicon"></i>
                <?=__('Publish new ')?>
            </a>                
        </div>	
	</div><!--/.nav-collapse -->
</nav>

<?if (!Auth::instance()->logged_in()):?>
	<div id="login-modal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                  <a class="close" data-dismiss="modal" >&times;</a>
                  <h3><?=__('Login')?></h3>
                </div>
                
                <div class="modal-body">
    				<?=View::factory('pages/auth/login-form')?>
        		</div>
            </div>
        </div>
    </div>
    
    <div id="forgot-modal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                  <a class="close" data-dismiss="modal" >&times;</a>
                  <h3><?=__('Forgot password')?></h3>
                </div>
                
                <div class="modal-body">
    				<?=View::factory('pages/auth/forgot-form')?>
        		</div>
            </div>
        </div>
    </div>
    
     <div id="register-modal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                  <a class="close" data-dismiss="modal" >&times;</a>
                  <h3><?=__('Register')?></h3>
                </div>
                
                <div class="modal-body">
    				<?=View::factory('pages/auth/register-form')?>
        		</div>
            </div>
        </div>
    </div>
<?endif?>
<div class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Modal title</h4>
      </div>
      <div class="modal-body">
        <p>One fine body&hellip;</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

