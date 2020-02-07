<?php
require_once "lib/Michelf/Markdown.php";
require_once "lib/Michelf/SmartyPants.php";

class Rooms {
  public static function HTMLroomSelector(){ ?>
    <div class='container'>
      <h4>Click <a href="https://roomballot.fitzjcr.com/rooms/wolfsoncourt">here</a> for information and photos about <strong>Wolfson Court</strong>.</h4>
<? /* Changed ballot image for this year due to refurbishment
      <img src='https://roomballot.fitzjcr.com/include/content/availablerooms.png' width="406" height="576" usemap="#map" /> */ ?>
      <img src='https://roomballot.fitzjcr.com/include/content/availablerooms_2020.png' width="406" height="576" usemap="#map" />
      <map name="map">
      <area shape="rect" coords="30,115,59,170" alt="L Staircase" href="https://roomballot.fitzjcr.com/rooms/fellowscourt" />
      <area shape="rect" coords="29,228,59,275" alt="N Staircase" href="https://roomballot.fitzjcr.com/rooms/fellowscourt" />
      <area shape="rect" coords="110,275,148,303" alt="P Staircase" href="https://roomballot.fitzjcr.com/rooms/fellowscourt" />
      <? /* Q and R not in ballot for this year
      <area shape="rect" coords="22,302,66,356" alt="Q Staircase" href="https://roomballot.fitzjcr.com/rooms/newcourt" />
      <area shape="rect" coords="22,357,68,415" alt="R Staircase" href="https://roomballot.fitzjcr.com/rooms/newcourt" /> */ ?>
      <area shape="rect" coords="26,418,60,494" alt="S Staircase" href="https://roomballot.fitzjcr.com/rooms/newcourt" />
      <area shape="rect" coords="61,475,118,514" alt="T Staircase" href="https://roomballot.fitzjcr.com/rooms/newcourt" />
      <area shape="rect" coords="122,475,143,532" alt="U Staircase" href="https://roomballot.fitzjcr.com/rooms/gatehousecourt" />
      <area shape="rect" coords="160,533,191,563" alt="V Staircase" href="https://roomballot.fitzjcr.com/rooms/gatehousecourt" />
      <area shape="rect" coords="192,535,225,564" alt="W Staircase" href="https://roomballot.fitzjcr.com/rooms/gatehousecourt" />
      <area shape="rect" coords="295,481,341,518" alt="X Staircase" href="https://roomballot.fitzjcr.com/rooms/wilsoncourt" />
      <area shape="rect" coords="347,426,385,476" alt="Y Staircase" href="https://roomballot.fitzjcr.com/rooms/wilsoncourt" />
      </map>
    </div>
<?}

  public static function HTMLroomView($room, $images, $quotes){ 
    $spec = Markdown::defaultTransform($room['spec']);
    $spec = SmartyPants::defaultTransform($spec);

    $desc = Markdown::defaultTransform($room['description']);
    $desc = SmartyPants::defaultTransform($room['description']);
  ?>
    <div class='container'>
      <h2><?= $room['name']; ?></h2>
      <div class="row">
        <div class="col-md-4">
          <?= Rooms::HTMLimageView($images); ?>
        </div>
        <div class="col-md-8">
          <div class="alert alert-info">
            <?= $spec; ?>
          </div>
          <span><b>Price Range: </b>£<?= $room['pricelow']; ?>-£<?= $room['pricehigh']; ?></span>
          <p>
            <?= $desc; ?>
          </p>
          <br /> <br />
    <?    if($quotes->num_rows > 0){ ?>
          <h2>Quotes</h2>
          <div>
            <?= Rooms::HTMLquoteView($quotes); ?>
          </div>
<?    } ?>
        </div>
      </div>
    </div>
<?}
  public static function HTMLquoteView($quotes){ 
    while($quote = $quotes->fetch_assoc()){ 
      $quotestr = Markdown::defaultTransform($quote['quote']);
      $quotestr = SmartyPants::defaultTransform($quotestr); ?>
      <blockquote class="blockquote">
        <?= $quotestr; ?> 
      </blockquote>
<?  }
  }

  public static function HTMLimageView($images){ 
    if($images->num_rows > 0){ ?>
      <div id="gallery">

<?    $descriptions = [];
      $plainDescriptions = [];
      $srcs = [];
      while($image = $images->fetch_assoc()){ 
        $srcs[] = $image['src'];
        $plainDescriptions[] = $image['description'];
        $description = Markdown::defaultTransform($image['description']);
        $descriptions[] = SmartyPants::defaultTransform($description); ?>
<?    } ?>

        <div id="large">
          <div class="thumbnail">
            <a href='<?= $srcs[0] ?>' id="gallery-link">
              <img id="gallery-large" src="<?= $srcs[0] ?>" title="<?= $plainDescriptions[0] ?>" style="width: 100%;"/>
              <div id="gallery-caption" class="caption">
                <?= $descriptions[0]; ?>
              </div>
            </a>
          </div>
        </div>
        <div id="smalls" class='ballot-smalls'>
<?        for($i = 0; $i < count($srcs); $i++){ ?>
            <a href="<?= $srcs[$i] ?>">
              <img class="ballot-gallery" src="<?= $srcs[$i]; ?>" title="<?= $plainDescriptions[$i]; ?>" width=100 />
            </a>
<?        } ?>
        </div>
      </div>
      <script>
        var galleryImg = document.getElementById("gallery-large");
        var galleryLnk = document.getElementById("gallery-link");
        var galleryDsc = document.getElementById("gallery-caption");
        var smallImages = document.getElementById("smalls").getElementsByTagName("a");

        var descs = [<?= '"'.join('", "', array_map(function($s){ return str_replace("\n", " ", addslashes($s)); }, $descriptions)).'"'; ?>];

        for(i = 0; i < smallImages.length; i++){
          smallImages[i].ord = i;
          smallImages[i].onclick = function(e){
            var img = this.getElementsByTagName("img")[0]
            galleryImg.src = img.src;
            galleryImg.attributes['title'] = img.attributes['title'];
            galleryLnk.attributes["href"].value = img.src;
            galleryDsc.innerHTML = descs[this.ord];
            e.preventDefault();
            return false;
          }
        }
      </script>
<?  }
  }
}
