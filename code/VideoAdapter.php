<?php

use FFMpeg\FFMpeg;

class VideoAdapter extends \CropperField\Adapter\GenericField {

	public function getFile() {
		$loadedFile = $this->getFormField()->getItems()->first();
		if(!$loadedFile) {
			return new \File();
		}
		return $loadedFile;
	}

	/**
	 * @return \Image
	 */
	public function getSourceImage() {
		$image = new Image();
		$video = $this->getFile();
		$videoPath = $video->getFullPath();
		if(!$video instanceof \File) {
			throw new UploadField_BadFileTypeException;
		}
		// FFMpeg information shall be pushed to the PHP system error_log
		$logger = new \Monolog\Logger('FFMpegErrorLogger');
		$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

		// Create the FFMpeg instance and open the file
		$ffmpeg = \FFMpeg\FFMpeg::create(array(), $logger);
		try {
			$video = $ffmpeg->open($videoPath);
		}
		catch(\FFMpeg\Exception\RuntimeException $e) {
			return $image;
		}
		$frame = $video->frame(
			\FFMpeg\Coordinate\TimeCode::fromSeconds(10)
		);
		// Save the frame as a JPEG and create a file around it
		list($label, $extension) = explode('.', basename($videoPath));
		$frameFile = ASSETS_PATH . '/' . $label . '.jpg';
		$frame->save($frameFile);
		$image->Filename = $frameFile;
		$image->write();
		return $image;
	}

}
