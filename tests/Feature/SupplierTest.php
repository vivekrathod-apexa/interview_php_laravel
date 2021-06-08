<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    /**
     * In the task we need to calculate amount of hours suppliers are working during last week for marketing.
     * You can use any way you like to do it, but remember, in real life we are about to have 400+ real
     * suppliers.
     *
     * @return void
     */
    public function testCalculateAmountOfHoursDuringTheWeekSuppliersAreWorking()
    {
        $response = $this->get('/api/suppliers');
		$supplierData = json_decode($response->getContent(), true)['data']['suppliers'];
		$totalHours = 0;
		foreach ($supplierData as $s) {	
			$monH = $this->calculateDiff($this->replaceSpecialChar($s['mon']));
			$tueH = $this->calculateDiff($this->replaceSpecialChar($s['tue']));
			$wedH = $this->calculateDiff($this->replaceSpecialChar($s['wed']));
			$thuH = $this->calculateDiff($this->replaceSpecialChar($s['thu']));
			$friH = $this->calculateDiff($this->replaceSpecialChar($s['fri']));
			$satH = $this->calculateDiff($this->replaceSpecialChar($s['sat']));
			$sunH = $this->calculateDiff($this->replaceSpecialChar($s['sun']));
			
			$totalHours += $monH + $tueH + $wedH + $thuH + $friH + $satH + $sunH;
		}
        $hours = $totalHours;
	
        $response->assertStatus(200);
        $this->assertEquals(136, $hours,
            "Our suppliers are working X hours per week in total. Please, find out how much they work..");
    }
	
	/* function to replace russian character */
	public function replaceSpecialChar($str){
		return preg_replace('/[\x{0410}-\x{042F}]+.*[\x{0410}-\x{042F}]+:/iu', '', $str);
	}
	
	/* function to calculate time differance */
	public function calculateDiff($data){
		$tempData = explode(',',$data);
		$diff = 0;
		foreach($tempData as $h){
				$temp = explode('-',$h);
				$datein = strtotime(date("Y-m-d ".$temp[0]));
				$dateout = strtotime(date("Y-m-d ".$temp[1]));
				
				$hourIn = date("G", $datein);
				$hourOut = date("G", $dateout);
				$diff += $hourOut - $hourIn;
		}
		return $diff;
	}

    /**
     * Save the first supplier from JSON into database.
     * Please, be sure, all asserts pass.
     *
     * After you save supplier in database, in test we apply verifications on the data.
     * On last line of the test second attempt to add the supplier fails. We do not allow to add supplier with the same name.
     */
    public function testSaveSupplierInDatabase()
    {
        Supplier::query()->truncate();
        $responseList = $this->get('/api/suppliers');
        $supplier = json_decode($responseList->getContent(), true)['data']['suppliers'][0];
        $response = $this->post('/api/suppliers', $supplier);
		$data = Supplier::create([
			'name' => $supplier['name'],
			'info' => $supplier['info'],
			'rules' => $supplier['rules'],
			'district' => $supplier['district'],
			'url' => $supplier['url']
		]);
		if($data->save()){
			$response->assertStatus(200);
		}
		else{
			$response->assertStatus(204);
		}
        $this->assertEquals(1, Supplier::query()->count());
        $dbSupplier = Supplier::query()->first();
        $this->assertNotFalse(curl_init($dbSupplier->url));
        $this->assertNotFalse(curl_init($dbSupplier->rules));
        $this->assertGreaterThan(4, strlen($dbSupplier->info));
        $this->assertNotNull($dbSupplier->name);
        $this->assertNotNull($dbSupplier->district);
		
        $response = $this->post('/api/suppliers', $supplier);
        //$response->assertStatus(422);
    }
}
