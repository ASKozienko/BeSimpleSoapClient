<?php

/*
 * This file is part of the BeSimpleSoapClient.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapClient;

use BeSimple\SoapCommon\Helper;
use BeSimple\SoapCommon\Mime\Part as MimePart;
use BeSimple\SoapCommon\SoapKernel;
use BeSimple\SoapCommon\SoapRequest as CommonSoapRequest;
use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;
use BeSimple\SoapCommon\Converter\TypeConverterInterface;

/**
 * MTOM type converter.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class MtomTypeConverter
{
    /**
     * {@inheritDoc}
     */
    public function getTypeNamespace()
    {
        return 'http://www.w3.org/2001/XMLSchema';
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeName()
    {
        return 'base64Binary';
    }

    /**
     * {@inheritDoc}
     */
    public function convertXmlToPhp($data, $soapKernel)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($data);

        $includes = $doc->getElementsByTagNameNS(Helper::NS_XOP, 'Include');
        $include = $includes->item(0);

        $ref = $include->getAttribute('myhref');

        if ('cid:' === substr($ref, 0, 4)) {
            $contentId = urldecode(substr($ref, 4));

            if (null !== ($part = $soapKernel->getAttachment($contentId))) {

                return $part->getContent();
            } else {

                return null;
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function convertPhpToXml($data, $soapKernel)
    {
        $part = new MimePart($data);
        $contentId = trim($part->getHeader('Content-ID'), '<>');

        $soapKernel->addAttachment($part);

        $doc = new \DOMDocument();
        $node = $doc->createElement($this->getTypeName());

        // add xop:Include element
        $xinclude = $doc->createElementNS(Helper::NS_XOP, Helper::PFX_XOP . ':Include');
        $xinclude->setAttribute('href', 'cid:' . $contentId);
        $node->appendChild($xinclude);

        return $doc->saveXML();
    }
}
