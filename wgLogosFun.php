<?php
error_reporting( -1 );
ini_set( 'display_errors', 1 );
$wgShowExceptionDetails = true;

class WgLogosFun {

	public const FLOWER = 'FLOWER_POWER';
	public const GERRIT = 'GERRIT';

	public function __construct( $type = self::FLOWER, $gitRoot )
	{
		$this->gitRoot = $gitRoot;
		$this->renderType = $type;
		$this->centerX = $this->width / 2;
		$this->centerY = $this->height / 2;
		$this->color = hexdec($this->random_color());
		$this->petalSize = random_int(2,20);
		$this->petalSize2 = random_int(2,20);

		if($this->renderType == self::GERRIT) {

			$this->branch = str_replace("\n", '', shell_exec("cd $this->gitRoot; git rev-parse --symbolic-full-name --abbrev-ref HEAD"));
			$this->segments = str_split($this->branch, 20);
			array_unshift($this->segments, 'Current branch:');
			$this->segmentCount = count( $this->segments );

			$changes = shell_exec("cd $this->gitRoot; git status --porcelain");
			$changesSegments = explode("\n", $changes);

			$this->countModified = 0;
			$this->countAdded = 0;
			$this->countUntracked = 0;

			foreach ($changesSegments as $segment) {
				$segment = trim($segment);
				if(substr( $segment, 0, 1 ) === 'M'){
					$this->countModified+=1;
				} else if(substr( $segment,0, 2 ) === '??'){
					$this->countUntracked+=1;
				} else if(substr( $segment,0, 1 ) === 'A'){
					$this->countAdded+=1;
				}

			}


		}
	}

	private $width = 160;
	private $height = 160;
	private $numberOfFrames = 80;

	function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
	}

	function random_color() {
		return $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
	}

	private function createFlower( &$imageResource, $i ) {

		$origoX = $this->centerX;
		$origoY = $this->centerY;
		$x = $origoX + cos($i/2.0)*$i;
		$y = $origoY + sin($i/2.0)*$i;
		$currentPos = (360 / $this->numberOfFrames) * $i;

		$values = [
			$origoX-$this->petalSize, $origoY-$this->petalSize,
			$x, $y,
			$origoX+$this->petalSize, $origoY+$this->petalSize
		];

		$values2 = [
			$origoX-$this->petalSize2, $origoY-$this->petalSize2,
			$x, $y,
			$origoX+$this->petalSize2, $origoY+$this->petalSize2
		];

		imagefilledpolygon($imageResource, $values, 3, $this->color*$i);
		imagefilledpolygon($imageResource, $values2, 3, $this->color*$i*$i);
		if($i > 0) {
			imagefilledarc($imageResource, $this->centerX, $this->centerY, $this->petalSize*4, $this->petalSize*4, 0, $currentPos, $this->centerColor, IMG_ARC_PIE);
		}
	}

	private function createGitStatus( &$imageResource, $frameNumber ) {

		$topPadding = 20;
		$firstAnimationPosition = $frameNumber / ( $this->numberOfFrames );
		$fontSize = 3;

		// 1. draw branch
		if($firstAnimationPosition < 0.19) {
			for ($i = 0; $i < count($this->segments); $i++) {
				imagestring($imageResource, $fontSize, 10, $topPadding + $topPadding*$i, $this->segments[ $i ], $this->color*$frameNumber);
			}
		}

		if($firstAnimationPosition >= 0.19 && $firstAnimationPosition < 0.25) {
			imagefilledrectangle( $imageResource, 0,0, $this->width, $this->height, 0xFFFFFF);
		}

		// 2. draw untracked files
		if($firstAnimationPosition > 0.25 && $firstAnimationPosition < 0.5) {
			imagestring($imageResource, $fontSize, 10, $topPadding, "Untracked: $this->countUntracked", $this->color*$frameNumber);
		}

		// 3. draw added files
		if($firstAnimationPosition > 0.5 && $firstAnimationPosition < 0.75) {
			imagestring($imageResource, $fontSize, 10, $topPadding + 1 * 20, "Added: $this->countAdded", $this->color*$frameNumber);
		}
		// 4. draw modified files
		if($firstAnimationPosition > 0.75 && $firstAnimationPosition < 1.0) {
			imagestring($imageResource, $fontSize, 10, $topPadding + 2 * 20, "Modified: $this->countModified", $this->color*$frameNumber);
		}
	}

	// such art much awe
	public function muchArt() {
		$frames = $this->createFrames();
		return $this->createGif($frames);
	}

	private function createFrames () {

		$frames = [];
		$imageResource = imagecreatetruecolor( $this->width, $this->height );
		$bgColor = imagecolortransparent( $imageResource );
		$this->centerColor = imagecolortransparent( $imageResource, 0xFFFF00 );

		// Make the background white
		imageFill( $imageResource, 0, 0, 0xFFFFFF );
		for($frameNumber = 0; $frameNumber < $this->numberOfFrames; $frameNumber++) {
			// Create a new image instance

			switch ($this->renderType){
				case WgLogosFun::FLOWER:
					$this->createFlower($imageResource, $frameNumber);
					break;
				case WgLogosFun::GERRIT:
					$this->createGitStatus($imageResource, $frameNumber );
					break;
			}
			// Output the image to browser
			$fileName = "/tmp/test$frameNumber.gif";
			$frames[] = $fileName;
			imagegif($imageResource, $fileName);


		}
		//imagedestroy($imageResource);

		return $frames;
	}

	private $animationSpeed = 2;

	private function updateAnimationSpeed(&$animationSpeed ) {
		switch ($this->renderType){
			default:
				$animationSpeed = $animationSpeed + $animationSpeed*0.01;
				break;
			case WgLogosFun::GERRIT:
				$animationSpeed = 10;
				break;
		}
	}

	private function createGif( &$files ) {
		$GIF = new Imagick();
		$GIF->setFormat("gif");

		$animationSpeed = 2;
		for ($i = 0; $i < sizeof( $files ); ++$i) {
			$this->updateAnimationSpeed($animationSpeed);

			$frame = new Imagick();
			$frame->readImage($files[$i]);
			$frame->setImageDelay($animationSpeed);
			$GIF->addImage($frame);
		}

		if($this->renderType === self::GERRIT) {
			$GIF = $GIF->deconstructImages();
			$GIF->setImageIterations(0);
		}


		return $GIF;
	}
}

$gitRoot = '/var/www/mediawiki/extensions/Wikibase/';

$art = new WgLogosFun(WgLogosFun::GERRIT, $gitRoot);
try {
	$blob = $art->muchArt()->getImagesBlob();
	header('Content-Type: image/gif');
	echo $blob;
} catch (Exception $exception) {
	throw $exception;
}


