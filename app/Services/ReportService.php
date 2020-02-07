<?php
/**
 * Created by PhpStorm.
 * User: zippyttech
 * Date: 18/09/18
 * Time: 03:45 PM
 */

namespace App\Services;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Self_;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportService
{
    private static $data = [];
    private static $index = [];
    private static $external=false;
    private static $dataPerSheet = [];
    private static $indexPerSheet =[];
    private static $title;
    private static $name;
    private static $username;
    private static $date;
    private static $user;
    private static $log_url = null;
    private static $account = null;
    private static $orientation = "portrait";
    private static $colors = ["primary"=>'#E92610',"secondary"=>'#f2f2f2',"auxiliary"=>'#ffffff'];
    public static $report;
    private static $returnRaw = false;


    /**
     * @param $html
     * @param $title
     * @param null $fi
     * @param null $ff
     * @param bool $multisheet
     * @param null $numSheet
     * @return Dompdf|\Illuminate\Http\JsonResponse|string|null
     */
    public function report($html, $title, $fi=null, $ff=null, $multisheet=false, $numSheet=null)
    {
        self::$title = $title;
        self::$name = explode(" ",$title)[0].'_'.time();
        self::setImage(rtrim(app()->basePath('public/images/zipi.png'), '/'));

        if(isset($_GET['format']))
        {
            switch ($_GET['format'])
            {
                case "csv":
                    self::$report =  self::csv($fi,$ff);
                    break;
                case "pdf":
                    self::$report =  self::pdf($html);
                    break;
                case "xls" AND !$multisheet:
                    self::$report = self::excel( $fi, $ff);
                    break;
                case "xls" AND $multisheet:
                    self::$report =  self::excelWorksheet($numSheet,$fi,$ff);
                    break;

            }
        }else{
            self::$report = self::pdf($html);
        }

        return self::$report;

    }


    /**
     * @param bool $flag
     */
    public function external($flag = true){
        self::$external = $flag;
    }
    public function transmissionRaw(){
        self::$returnRaw = true;
    }

    /**
     * @param $orientation
     */
    public function orientation($orientation){
        self::$orientation = $orientation;
    }

    /**
     * @param $user
     */
    public function user($user){
        $this->username($user->username);
        $this->date($user->account);
        self::$user = $user;
    }

    /**
     * @param $url
     */
    public function setImage($url){
        if (!self::$log_url){
            self::$log_url = $url;
        }
    }

    /**
     * @param $username
     */
    public function username($username){
        self::$username = $username;
    }

    /**
     * @param $info
     */
    public function data($info){
        self::$data = $info;
    }

    /**
     * @param $array_index
     */
    public function index($array_index){
        self::$index = $array_index;
    }

    /**
     * @param $array_info
     */
    public function dataPerSheet($array_info){
        self::$dataPerSheet = $array_info;
    }

    /**
     * @param $account
     */
    public function date($account){
        self::$date  =  ($account==5 OR $account==7) ?
            Carbon::now()->setTimezone("America/Caracas")->toDateTimeString() :
            Carbon::now()->setTimezone("America/Panama")->toDateTimeString();
    }

    /**
     * @param $array_index
     */
    public function indexPerSheet($array_index){
        self::$indexPerSheet = $array_index;
    }

    /**
     * @param string|null $fi
     * @param string|null $ff
     * @return \Illuminate\Http\JsonResponse|null
     */
    public static function excel(string $fi = null, string $ff=null){
        try{
            $toExcel = $arrayData = [];
            $spreadsheet = new Spreadsheet();
            $pathLogo = self::$log_url;
            $sheet = self::getDefaultConfiguration($spreadsheet,$pathLogo);

            //Parsear la información a pasar
            foreach (self::$index as $title => $value) {
                $arrayData[0][]=$title;
            }

            $total_operaciones = 0;
            foreach (self::$data as $key){
                $i=1;
                $toArray = is_object($key) ? $key : is_array($key) ? (object) $key : null;
                foreach (self::$index as $title => $value) {
                    $toExcel[$i] = $toArray->$value ?? null;
                    $i++;
                }
                $total_operaciones++;
                $arrayData[] = $toExcel;
            }
            $arrayData[] = ['Total de Operaciones', $total_operaciones];
           // $sheet->getActiveSheet()->setCellValue("A6","Total de Operaciones: ");
           // $sheet->getActiveSheet()->setCellValue("B6",$total_operaciones); //->refreshColumnDimensions();
            $sheet->getActiveSheet()->fromArray($arrayData, "Sin Registro", 'A8')->refreshColumnDimensions();

            $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="report.xlsx"');

            // Add Custom URL
            if (self::$external) {
                $writer->save('./reports/'.self::$name.'.xls');
                //return response()->json(["message"=>'reports/'.self::$name.'.xls'],200); //env('CUSTOM_URL').
                return response()->json(["message"=> env('CUSTOM_URL').'/reports/'.self::$name.'.xls'],200); //env('CUSTOM_URL').
            }

            $writer->save("php://output");

            return null;
        }catch (Exception $exception){
            Log::critical($exception->getMessage());
            return response()->json(["message"=>"Error al crear el reporte"],500);
        }
    }


    /**
     * @param string $name
     * @param int $numWorksheet
     * @param string|null $fi
     * @param string|null $ff
     * @return \Illuminate\Http\JsonResponse|null
     */
    public static function excelWorksheet(string $name, int $numWorksheet, string $fi = null, string $ff=null){
        $spreadsheet = new Spreadsheet();

        $dataPerSheet = self::$dataPerSheet;
        $indexPerSheet = self::$indexPerSheet;

        try{
            $pathLogo = self::$log_url;
            $letter = range("A","Z");
            for($j=0; $j<$numWorksheet;$j++){
                if ($j>0){
                    $worksheet = $spreadsheet->createSheet();
                    $worksheet->setTitle('Hoja'.$letter[$j]);
                }else{
                    $sheet = self::getMultisheetDefaultConfiguration($spreadsheet,$name,count($dataPerSheet[$j]),
                        count($indexPerSheet[$j])-1,$pathLogo);
                    $worksheet = $sheet->getActiveSheet();
                }

                $sheet->getActiveSheet()->setCellValue("A6","Fecha de Emision: ");
                $sheet->getActiveSheet()->setCellValue("B6",self::$date);
                $sheet->getActiveSheet()->setCellValue("C6",'Usuario: ');
                $sheet->getActiveSheet()->setCellValue("D6",self::$username);

                $arrayData[$j][]=$indexPerSheet[$j];

                foreach ($dataPerSheet[$j] as $key){
                    $toExcel = [];
                    $i=1;
                    $toArray = is_object($key) ? $key : is_array($key) ? (object) $key : null;
                    foreach ($indexPerSheet[$j] as $title => $value) {
                        $toExcel[$i] = $toArray->$value ?? null;
                        $i++;
                    }
                    $arrayData[$j][] = $toExcel;
                }
                $worksheet->fromArray($arrayData[$j], "Sin Registro", 'A7');

                $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="reporte.xlsx"');
            header('Access-Control-Allow-Origin:*');

            if (self::$external) {
                $writer->save('./reports/'.self::$name.'.xls');
                return response()->json(["message"=>'reports/'.self::$name.'.xls'],200);
            }
            return $writer->save("php://output");

        }catch (Exception $exception){
            Log::critical($exception->getMessage());
            return response()->json(["message"=>"Error al crear el reporte"],500);
        }
    }


    /**
     * @param $html
     * @return Dompdf|\Illuminate\Http\JsonResponse|string
     */
    public static function pdf($html)
    {
        $html = self::getHtml($html);
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $pdf = new DOMPDF($options);

        $pdf->setPaper("Letter", self::$orientation);
        $pdf->loadHtml($html);
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $footer = $canvas->open_object();

        $w = $canvas->get_width();

        $h = $canvas->get_height();

        $canvas->page_text($w-60,$h-28,"Página {PAGE_NUM} de {PAGE_COUNT}", $pdf->getFontMetrics()->getFont("helvetica", "bold"),6);
        $canvas->page_text($w-590,$h-28,"",$pdf->getFontMetrics()->getFont("helvetica", "bold"),6);

        $canvas->close_object();
        $canvas->add_object($footer,"all");

        // Add Custom URL
        if (self::$external) {
            //$pdf->save('./reports/'.$name.'.pdf');
            $output = $pdf->output();
            file_put_contents('./reports/'.self::$name.'.pdf', $output);
            return response()->json(["message"=>env('CUSTOM_URL').'/reports/'.self::$name.'.pdf'],200);
        }

        if (self::$returnRaw){
            header('content-type:application/pdf');
            return $pdf->output();
        }


        $pdf->stream('report.pdf', array('Attachment'=>0));

        return $pdf;
    }

    /**
     * @param $html
     * @return false|string
     */
    public static function getHtml($html)
    {
        ob_start();
        $algo = self::$dataPerSheet;
        $index  = self::$index;
        $data = self::$data;
        $title = self::$title;
        $username = self::$username;
        $date = self::$date;
        $colors = self::$colors;
        $logo =  self::$log_url;
        include(resource_path("Reports/{$html}.php"));

        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    /**
     * @param null $fi
     * @param null $ff
     * @return \Illuminate\Http\JsonResponse|string|null
     */
    public static function csv($fi = null, $ff=null){
        try{

            $spreadsheetCsv = new Spreadsheet();

            $sheet = self::getDefaultConfiguration($spreadsheetCsv);

            if ($fi==1){
                $dt = Carbon::now();
                $ff = date('Y-m-d', strtotime('next monday'));
                $fi = $dt->isMonday() ? date('Y-m-d', $dt) : date('Y-m-d', strtotime("last Monday"));
            }
            /*if (strtotime($fi) AND strtotime($ff)){
                $spreadsheetCsv->getActiveSheet()->setCellValue("A4","Desde: ");
                $spreadsheetCsv->getActiveSheet()->setCellValue("B4",$fi);
                $spreadsheetCsv->getActiveSheet()->setCellValue("C4",'Hasta: ');
                $spreadsheetCsv->getActiveSheet()->setCellValue("D4","$ff");
            }*/

            //Parsear la información a pasar
            foreach (self::$index as $title => $value) {
                $arrayData[0][]=$title;
            }
            foreach (self::$data as $key){
                $i=1;
                $toArray = is_object($key) ? $key : is_array($key) ? (object) $key : null;
                foreach (self::$index as $title => $value) {
                    $toExcel[$i] = $toArray->$value ?? null;
                    $i++;
                }
                $arrayData[] = $toExcel;
            }

            $sheet->getActiveSheet()->fromArray($arrayData, "Sin Registro", 'A7');

            $writer =IOFactory::createWriter($sheet, 'Csv');

            if (self::$external) {
                $writer->save('./reports/'.self::$name.'.csv');
                return response()->json(["message"=>'reports/'.self::$name.'.csv'],200);
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="report.csv"');
            $writer->save("php://output");
            return null;
        }catch (Exception $exception){
            Log::critical($exception->getMessage());
            return $exception->getMessage();
        }
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param $name
     * @param null $pathLogo
     * @param string $columnStart
     * @param string $rowStart
     * @return \Exception|Exception|Spreadsheet
     */
    protected static function getDefaultConfiguration(Spreadsheet $spreadsheet, $pathLogo=null, $columnStart="A", $rowStart='1')
    {
        try{
            $alphabet = range('A', 'Z');
            $totalColumns = count(self::$index) -1 ;
            $totalRows = count(self::$data) +2;

            for ($i="A";$i<"Z";$i++){
                $spreadsheet->getActiveSheet()
                    ->getColumnDimension($i)
                    ->setAutoSize(true);
            }

            $spreadsheet->getActiveSheet()->setCellValue($columnStart.$rowStart,"Reporte de " . self::$title);
            $spreadsheet->getActiveSheet()->mergeCells($columnStart.$rowStart.':'.$alphabet[$totalColumns] . '5');
            $spreadsheet->getActiveSheet()->getStyle($columnStart.$rowStart)->getFont()->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle($columnStart.$rowStart)->getAlignment()
                ->applyFromArray([
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]);

            $spreadsheet->getActiveSheet()->getStyle($columnStart.$totalRows.':' . $alphabet[$totalColumns] . $totalRows)
                ->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
            $spreadsheet->getActiveSheet()->getStyle($columnStart.$rowStart.':'.$alphabet[$totalColumns].'1')->getFill()->setFillType(Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->getStyle('A1:'.$alphabet[$totalColumns].'1')->getFont()->getColor()->setARGB('00000000');

            if ($pathLogo){
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($pathLogo);
                $drawing->setHeight(30);
                $drawing->setWidth(100);
                $drawing->setCoordinates($alphabet[$totalColumns-1].$rowStart);
                $drawing->setWorksheet($spreadsheet->getActiveSheet());
            }

            return $spreadsheet;
        }catch (Exception $exception){
            Log::critical($exception->getMessage() . $exception->getLine() . $exception->getFile());
            return $spreadsheet;
        }
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param $totalRows
     * @param $totalColumns
     * @param null $pathLogo
     * @param string $columnStart
     * @param string $rowStart
     * @return Spreadsheet|string
     */
    private static function getMultisheetDefaultConfiguration(Spreadsheet $spreadsheet,$totalRows, $totalColumns, $pathLogo=null, $columnStart="A", $rowStart='1'){
        try{
            $alphabet = range('A', 'Z');
            for ($i="A";$i<"Z";$i++){
                $spreadsheet->getActiveSheet()->getColumnDimension($i)->setAutoSize(true);

            }
            $spreadsheet->getActiveSheet()->setCellValue($columnStart.$rowStart,"Reporte de " . self::$title);
            $spreadsheet->getActiveSheet()->mergeCells($columnStart.$rowStart.':'.$alphabet[$totalColumns] . '5');
            $spreadsheet->getActiveSheet()->getStyle($columnStart.$rowStart)->getFont()->setSize(16);
            $spreadsheet->getActiveSheet()->getStyle($columnStart.$rowStart)->getAlignment()
                ->applyFromArray([
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]);

            $spreadsheet->getActiveSheet()->getStyle($columnStart.$totalRows.':' . $alphabet[$totalColumns] . $totalRows)
                ->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
            $spreadsheet->getActiveSheet()->getStyle($columnStart.$rowStart.':'.$alphabet[$totalColumns].'1')->getFill()->setFillType(Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->getStyle('A1:'.$alphabet[$totalColumns].'1')->getFont()->getColor()->setARGB('00000000');

            if ($pathLogo){
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($pathLogo);
                $drawing->setHeight(30);
                $drawing->setWidth(100);
                $drawing->setCoordinates($alphabet[$totalColumns].$rowStart);
                $drawing->setWorksheet($spreadsheet->getActiveSheet());
            }
            return $spreadsheet;
        }catch (Exception $exception){
            Log::critical($exception->getMessage());
            return $spreadsheet;
        }
    }

    /**
     * @param $account
     */
    public function getAccountInfo($account){
        $client = new Client();
        $url = substr(env('ACCOUNT_URL'), -1) == "/" ? env('ACCOUNT_URL') : env('ACCOUNT_URL') . "/";

        $account = $client->get($url.'accounts/' . $account);
        if ($account->getStatusCode() == 200){
            self::$account = json_decode($account->getBody())->Cuenta;
        }
        $this->extractInfo();
    }

    public function extractInfo(){
        if(count((array) self::$account)>0){
            foreach (self::$account->images as $image) {
                if ($image->name == 'LOGO') {
                    self::$log_url = $image->value;
                }
            }
            foreach (self::$account->styles as $style){
                if ($style->key == 'primary_color'){
                    self::$colors['primary'] = $style->value;
                }
                if ($style->key == 'auxiliary_color'){
                    self::$colors['auxiliary'] = $style->value;
                }
                if ($style->key == 'secondary_color'){
                    self::$colors['secondary'] = $style->value;
                }
            }
            $timezone = explode(',',self::$account->timezone);
            self::$date = count($timezone)>1 ?
                Carbon::now()->setTimezone($timezone[1])->toDateTimeString() :
                Carbon::now()->setTimezone('America/Panama')->toDateTimeString();
        }
    }
}
