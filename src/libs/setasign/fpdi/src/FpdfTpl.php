<?php

/**
 * This file is part of FPDI
 *
 * @copyright Copyright (c) 2020 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi;

use setasing\Fpdfd\FPDF;

include dirname(__FILE__) . '/../../fpdf/fpdf.php';
/**
 * Class FpdfTpl
 *
 * This class adds a templating feature to FPDF.
 */
class FpdfTpl extends FPDF
{
    use FpdfTplTrait;
}
