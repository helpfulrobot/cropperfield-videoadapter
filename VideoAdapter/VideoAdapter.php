<?php namespace CropperField\VideoAdapter;

/**
 * VideoAdapter
 * @author @willmorgan
 */

use FFMpeg\FFMpeg;

use Image;
use File;

use Monolog\Logger as Logger;
use Monolog\Handler\ErrorLogHandler as ErrorHandler;

class VideoAdapter extends \CropperField\Adapter\GenericField {

	public function getFile() {
		$loadedFile = $this->getFormField()->getItems()->first();
		if(!$loadedFile) {
			return new File();
		}
		return $loadedFile;
	}

	/**
	 * @param string $videoPath FULL path to the video
	 * @return \FFMpeg\FFmpeg
	 */
	protected function openVideo() {
		$videoPath = $this->getFile()->getFullPath();
		// FFMpeg information shall be pushed to the PHP system error_log
		$logger = new Logger('FFMpegErrorLogger');
		$logger->pushHandler(new ErrorHandler());

		// Create the FFMpeg instance and open the file
		$ffmpeg = \FFMpeg\FFMpeg::create(array(), $logger);
		return $ffmpeg->open($videoPath);
	}

	/**
	 * @return \Image
	 */
	public function getSourceImage() {
		$image = new Image();
		$video = $this->getFile();
		$videoPath = $video->getFullPath();
		if(!$video instanceof File) {
			throw new UploadField_BadFileTypeException;
		}
		try {
			$video = $this->openVideo($videoPath);
		}
		catch(\FFMpeg\Exception\RuntimeException $e) {
			return $image;
		}
		$frame = $video->frame(
			\FFMpeg\Coordinate\TimeCode::fromSeconds(10)
		);
		// Save the frame as a JPEG and create a file around it
		list($label, $extension) = explode('.', basename($videoPath));
		$frameFile = '/' . $label . '.jpg';

		// Absolute path needed for saving of the source image
		$frame->save(ASSETS_PATH . $frameFile);

		// Relative path needed for the file object
		$image->Filename = ASSETS_DIR . $frameFile;
		$image->write();
		return $image;
	}

}
