<?php

namespace imfa\Http\Controllers;

use Barryvdh\DomPDF\PDF;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use imfa\Http\Requests;

use imfa\Modelos\Documento\Cabecera;
use imfa\Modelos\Documento\CabeceraDetalle;
use imfa\Modelos\Nomencladores\Cliente;
use imfa\Modelos\Nomencladores\TipoDocumento;

use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function upXML()
    {
        return view('upfiles');
    }

    public function saveUpXml( Request $request ){

        $file = $request->file('file');
        $nombre = $file->getClientOriginalName();
        Storage::disk('ftpUp')->put($nombre,  \File::get($file));

        return redirect('/documentos');
    }


    public function downloads($id){
        $cabeceraInstance = Cabecera::find($id);
        $name = $cabeceraInstance->claveAcceso;
        $xml= $cabeceraInstance->xml ;
        header("Content-type: text/xml");
        header("Content-Disposition: attachment; filename= $name.xml ");
        echo $xml;

    }



    public function downPDF($id){
        $cabeceraInstance = Cabecera::find($id);
        $name = $cabeceraInstance->claveAcceso;
        $data = [];
        $pdf = \PDF::loadView('pdfview', $data );

        // http://imfa.es/imfa/logo-ludoteca.png
        return view('pdfview');
        //return $pdf->download( '' . $name . '.pdf');
    }




    public function ftpfile(Filesystem $filesystem){
        // $filesystem->get('imfacturacion.com/public/facturaCDATA.xml') ;
        // $filesystem->get('imfacturacion.com/public/factura.xml') ;

        $desde = 'XML' ;
        $destino = 'XMLTMP' ;

        $filesxmls = $filesystem->files('XML') ;

        foreach( $filesxmls as $fils ){
           //  echo '$fils: ' . $fils ;

            $xmlx = $filesystem->get($fils) ;
            $xmlstmp = new \SimpleXMLElement($xmlx);
            $xmlcomprobantetmp = new \SimpleXMLElement($xmlstmp->comprobante);
            echo ' secuencial: ' . $xmlcomprobantetmp->infoTributaria->secuencial;


            try{
                $filesystem->delete($fils) ;
            }catch (FileNotFoundException $e ){
                echo ' FileNotFoundException ' . $e ;
            }

        }


     /*   $xml = $filesystem->get('XML/0109201601179218265400120010010000000010000000115.xml') ;
        $xmls = new \SimpleXMLElement($xml);

        echo ' ---- $xml_object: '. $xmls->estado ;

        $xmlcomprobante = new \SimpleXMLElement($xmls->comprobante);
        echo ' $xmlcomprobante razonSocial: ' . $xmlcomprobante->infoTributaria->razonSocial;

        foreach ($xmlcomprobante->detalles->detalle as $detail) {
            echo ' __detalle precioTotalSinImpuesto = ', $detail->precioTotalSinImpuesto;
        }

        echo ' campoAdicional[0]: ' . $xmlcomprobante->infoAdicional->campoAdicional[0];*/

        //dd($xmlcomprobante)   ;


        // solo para xml tipo facturas, no autorizados
        /*$factura = $filesystem->get('imfacturacion.com/public/factura.xml');
        $facturas = new \SimpleXMLElement($factura);
        echo '  _codigo documento: ' . $facturas->infoTributaria->codDoc . '  ';*/



       // return  $xmls->estado ;

        return 'holaaaa ftpfile';
    }



}
