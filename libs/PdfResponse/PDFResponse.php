<?php

/**
 * @property-read mPDFExtended $mPDF
 */
class PDFResponse extends Object implements IPresenterResponse
{
	/** @var mixed */
	private $source;

        /**
         * path to mPDF.php
         * @var string
         */
        public static $mPDFPath;

        /**
         * Callback - create mPDF object
         * @var callback
         */
        public $createMPDF = null;

        const ORIENTATION_PORTRAIT  = "P";
        const ORIENTATION_LANDSCAPE = "L";

        /**
         * Specifies page orientation.
         * You can use constants:
         *   ORIENTATION_PORTRAIT (default)
         *   ORIENTATION_LANDSCAPE
         *
         * @var string
         */
        public $orientation = self::ORIENTATION_PORTRAIT;

        /**
         * Specifies format of the document
         * Allowed values:
         *   Values (case-insensitive)
         *   A0 - A10
         *   B0 - B10
         *   C0 - C10
         *   4A0
         *   2A0
         *   RA0 - RA4
         *   SRA0 - SRA4
         *   Letter
         *   Legal
         *   Executive
         *   Folio
         *   Demy
         *   Royal
         *   A (Type A paperback 111x178mm)
         *   B (Type B paperback 128x198mm)
         *
         * @var string
         */
        public $format = "A4";

        /**
         * Margins in this order:
         *   top
         *   right
         *   bottom
         *   left
         *   header
         *   footer
         *
         * @var string
         */
        public $margins = "16,15,16,15,9,9";

        /**
         * Author of the document
         * @var string
         */
        public $author = "Nette Framework - Pdf response";

        /**
         * Title of the document
         * @var string
         */
        public $title = "Unnamed document";

        /**
         * This parameter specifies the magnification (zoom) of the display when the document is opened.
         * Values (case-sensitive)
         *   fullpage: Fit a whole page in the screen
         *   fullwidth: Fit the width of the page in the screen
         *   real: Display at real size
         *   default: User's default setting in Adobe Reader
         *   INTEGER : Display at a percentage zoom (e.g. 90 will display at 90% zoom)
         *
         * @var string|int
         */
        public $displayMode = "default";

        /**
         * Specify the page layout to be used when the document is opened.
         * Values (case-sensitive)
         *   single: Display one page at a time
         *   continuous: Display the pages in one column
         *   two: Display the pages in two columns
         *   default: User's default setting in Adobe Reader
         * @var string
         */
        public $displayLayout = "continuous";

        /**
         * Nette Callbacks
         * @var array
         */
        public $onBeforeComplete = array();

        private $mPDF = null;

        function getMargins(){
            $margins = explode(",", $this->margins);
            if(count($margins) !== 6) {
                throw new InvalidStateException("You must specify all margins! For example: 16,15,16,15,9,9");
            }

            $dictionary = array(
                0 => "top",
                1 => "right",
                2 => "bottom",
                3 => "left",
                4 => "header",
                5 => "footer"
            );

            $marginsOut = array();
            foreach($margins AS $key => $val){
                $val = (int)$val;
                if($val <= 0) {
                    throw new InvalidArgumentException("Margin must be positive number!");
                }
                $marginsOut[$dictionary[$key]] = $val;
            }
            
            return $marginsOut;
        }

	/**
	 * @param  mixed  renderable variable
	 */
	public function __construct($source)
	{
                $this->createMPDF = array($this,"createMPDF");
		$this->source = $source;
	}



	/**
	 * @return mixed
	 */
	final public function getSource()
	{
		return $this->source;
	}



	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send()
	{
		if ($this->source instanceof ITemplate) {
			$html = $this->source->__toString();

		} else {
			$html = $this->source;
		}
                
                $mpdf = $this->getMPDF();
                $mpdf->SetAuthor($this->author);
                $mpdf->SetTitle($this->title);
                $mpdf->WriteHTML($html,2);
		
		$mpdf->OpenPrintDialog();

                $this->onBeforeComplete($mpdf);

                $mpdf->Output(String::webalize($this->title),'I');
	}


        /**
         * Returns mPDF object
         * @return mPDFExtended
         */
        public function getMPDF(){
                if(!$this->mPDF instanceof mPDF) {
                        if(!is_callable($this->createMPDF)) {
                            throw new InvalidStateException("Callback createMPDF is not callable!");
                        }
                        $mpdf = call_user_func($this->createMPDF, $this);
                        if(!($mpdf instanceof mPDF)) {
                            throw new InvalidStateException("Callback function createMPDF must return mPDF object!");
                        }
                        $this->mPDF = $mpdf;
                }
                return $this->mPDF;
        }



        /**
         * Creates and returns mPDF object
         * @param PDFResponse $response
         * @return mPDFExtended
         */
        public function createMPDF(){
		if(!self::$mPDFPath) {
			self::$mPDFPath = dirname(__FILE__)."/mpdf/mpdf.php";
		}
                $mpdfPath = Environment::expand(self::$mPDFPath);
                define('_MPDF_PATH',dirname($mpdfPath)."/");
                require($mpdfPath);

                $margins = $this->getMargins();

                //  [ float $margin_header , float $margin_footer [, string $orientation ]]]]]])
                $mpdf = new mPDFExtended(
                    'utf-8',            // string $codepage
                    $this->format,  // mixed $format
                    '',                 // float $default_font_size
                    '',                 // string $default_font
                    $margins["left"],   // float $margin_left
                    $margins["right"],  // float $margin_right
                    $margins["top"],    // float $margin_top
                    $margins["bottom"], // float $margin_bottom
                    $margins["header"], // float $margin_header
                    $margins["footer"], // float $margin_footer
                    $this->orientation
                );


                // Default
                //$mpdf->BiDirectional = false;

                // Zobraz v editoru celou stránku
                //$mpdf->SetDisplayMode('fullpage');

                //$mpdf->default_lineheight_correction = 1.2;
                return $mpdf;
        }

}