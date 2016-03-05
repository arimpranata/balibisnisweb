<?php require_once("couch/cms.php"); ?>
<cms:template title='Homepage' desc='Admin section for homepage' order='1'>
  <!-- Title IN -->
  <cms:editable name='page_title' type='text' label='Page title' order='1' />
  <cms:editable name='page_desc' type='richtext' label='Page description' order='3' />

  <!-- Slideshow -->
  <cms:repeatable name='profesi' label='Profesi' order='5'>
  <cms:editable name='profesi_icon' type='image' label='Profesi icon (Autocrop 125x125)' show_preview='1' preview_width='75' width='125' height='125' crop='1' />
  <cms:editable name='profesi_target' type='text' label='URL target' />
  <cms:editable name='profesi_desc' type='nicedit' label='Profesi description' />
  </cms:repeatable>

  <!-- Slideshow -->
  <cms:repeatable name='slide_show' label='Image Slide Show' order='6'>
  <cms:editable name='slide_photo' type='image' label='Photo slideshow' show_preview='1' preview_width='150' width='1440' height='600' crop='1' />
  </cms:repeatable>

  <!-- Meta -->
  <cms:editable label='Meta'name='meta_web' type='group' order='7' />
  <cms:editable type='text' name='meta_title' label='Meta title' group='meta_web' />
  <cms:editable type='text' name='meta_key' label='Meta keyword' group='meta_web' />
  <cms:editable type='textarea' name='meta_desc' label='Meta description' group='meta_web' />
</cms:template>

<cms:embed 'header.php' />
  <!-- SLIDER -->
  <div class="camera_wrap">
    <cms:show_repeatable 'slide_show'>
      <div data-src="<cms:show slide_photo />"  />"></div>
    </cms:show_repeatable>
  </div>
  <script>
  jQuery(function(){
  	jQuery('.camera_wrap').camera({
  		height: '40%',
  		loader: 'pie',
      navigation: true,
  		pagination: false,
  		thumbnails: false,
  		time: 4500
  	});
  });
  </script>

  <!-- CONTENT -->
  <div class="section">
      <div class="container">
          <div class="row">
              <div class="col-md-12">
                  <br>
                  <h1 class="text-center title"><cms:show page_title /></h1>
                  <div class="bottom_title center"></div>
              </div>
          </div>
          <div class="row">
              <div class="col-md-12">
                <cms:show page_desc /><br>
                <div class="col-center owl-profesi" align="center">
                  <cms:show_repeatable 'profesi' startcount='1'>
                    <a href="#" data-toggle="modal" data-target="#<cms:show profesi_target />"><img src="<cms:show profesi_icon />" /></a>
                  </cms:show_repeatable>
                </div>
                <cms:show_repeatable 'profesi' startcount='1'>
                <div class="modal fade" id="<cms:show profesi_target />">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-body" align="justify">
                        <cms:show profesi_desc />
                      </div>
                      <div class="modal-footer">
                        <a class="btn btn-default" data-dismiss="modal">close</a>
                      </div>
                    </div>
                  </div>
                </div>
                </cms:show_repeatable>
              </div>
          </div>
      </div>
  </div>

  <!-- PROGRAMS -->
  <div class="studi"><img src="<cms:show k_site_link />images/studi.png" class="center-block img-responsive"></div>
  <cms:pages masterpage='features2.php' startcount='1'>
  <cms:set sling="<cms:mod k_count '2' />" />
  <cms:set slingshoot='content_blue' />
  <cms:if sling=='0'><cms:set slingshoot='content_orange' /></cms:if>
  <div class="section" id="<cms:show slingshoot />">
      <div class="container">
          <div class="row">
              <div class="col-md-12 text-center">
                  <br>
                  <img src="<cms:show cover_photo />" class="center-block img-responsive">
                  <h1 class="heading_title"><cms:show page_title /></h1>
                  <p class="text-center"><cms:show page_desc /></p>
                  <br>
                  <a class="btn_more" href="<cms:show page_url />">INFO SELENGKAPNYA</a>
              </div>
          </div>
      </div>
  </div>
  <div class="section" id="artwork">
		<div class="owl-carousel no-margin">
			<cms:show_repeatable 'slide_show'>
        <a href="<cms:show page_url />" title="<cms:show page_url />"><img src="<cms:show slide_photo />" class="img-responsive" alt="<cms:show slide_photo />"></a>
      </cms:show_repeatable>
    </div>
  </div>
  </cms:pages>

 <!-- NEWS -->
  <div class="section" id="news">
    <div class="container">
      <div class="row">
          <div class="col-md-12" id="title_row">
              <h3 class="title">BERITA TERBARU</h3>
              <div class="bottom_title"></div>
          </div>
      </div>
      <div class="row">
        <div class="col-md-12 owl-news">
          <cms:pages masterpage="news.php" limit='8'>
              <div class="no_border no_padding img_hover">
                  <cms:show_repeatable 'slide_show' startcount='1' limit='1'>
                    <a href="<cms:show k_page_link />"><div class='phone-size' style="background:url(<cms:show slide_photo />); background-size: cover; width: 263px; height: 180px; ">&nbsp;</div></a>
                  </cms:show_repeatable>
                  <div class="caption">
                      <p class="news_date"><cms:date k_page_date formats='l d, Y' /></p>
                      <a class="news_title" href="<cms:show k_page_link />" title="<cms:show k_page_title />"><cms:show k_page_title />...</a>
                  </div>
              </div>
          </cms:pages>
        </div>
      </div>
      <div class="row">
          <div class="col-md-12">
              <a class="btn_collapse" href="news.php">berita lainnya&nbsp;<i class="et-down fa mark-o fa-angle-right"></i></a>
          </div>
      </div>

      <!-- JOBS -->
      <div class="row">
          <div class="col-md-12" id="title_row">
              <h3 class="title">LOWONGAN KERJA</h3>
              <div class="bottom_title"></div>
          </div>
      </div>
      <div class="row">
        <div class="col-md-12 owl-jobs">
          <cms:pages masterpage="jobs.php" limit='12'>
              <div class="no_border no_padding img_hover">
                  <div class="caption">
                    <a href="<cms:show k_page_link />"><h4><cms:show k_page_title /></h4></a>
                    <p class="news_date"><i class="-o fa fa-fw fa-lg -circle-o fa-clock-o"></i> <cms:date k_page_date formats='l d, Y' /></p><hr>
                    <p style="color: #333 !important;"><cms:show page_pos /></p>
                  </div>
              </div>
          </cms:pages>
        </div>
      </div>
      <div class="row">
          <div class="col-md-12">
              <a class="btn_collapse" href="jobs.php">Lowongan kerja lainnya&nbsp;<i class="et-down fa mark-o fa-angle-right"></i></a>
          </div>
      </div>

      <!-- FETAURED -->
      <div class="row" id="featured">
            <cms:folders masterpage='features.php' orderby='weight'>
            <div class="col-md-4 col-sm-4">
                <h3 class="title"><cms:show k_folder_title /></h3>
                <div class="bottom_title">&nbsp;</div>
                <div id="<cms:show k_folder_id />" data-interval="3000" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        <cms:pages masterpage='features.php' folder=k_folder_name startcount='0'>
                            <cms:if k_count=='0'>
                              <div class="item active">
                            <cms:else />
                              <div class="item">
                            </cms:if>
                              <a href="<cms:show page_url />" title="<cms:show page_url />"><img src="<cms:show cover_photo />"></a>

                              <div class="carousel-caption" style="background: <cms:zebra '#3a99ca' '#f17712' />; opacity: 0.8;">
                                <p><cms:show k_page_title /></p>
                              </div>
                            </div>
                        </cms:pages>
                    </div>
                </div>
            </div>
          </cms:folders>
        </div>
    </div>
  </div>
<cms:embed 'footer.php' />
<?php COUCH::invoke(); ?>