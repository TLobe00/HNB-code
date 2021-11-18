<?php

namespace App\Console\Commands;

use App\Models\scerror;
use App\Models\screquest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class pushSCRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stratacache:pushSCRequests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $hnb_url;
    private $hnb_api;
    private $hnb_user;
    private $hnb_pw;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->hnb_url = env('HNB_DOMAIN');
        $this->hnb_api = env('HNB_API_ID');
        $this->hnb_user = env('HNB_USER');
        $this->hnb_pw = env('HNB_PASS');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tickets = screquest::where('push_to_servicenow','=',1)->with('notes')->with('info')->get();

        foreach ($tickets as $ticket) {

/*
                    $data = "{'category':'Application Services','u_hnb_subcat':'E-Merchandising - Production','u_hnb_app_issues':'Functionality','cmdb_ci':'E-Merchandising - Production','u_environment':'Production','impact':4,'urgency':4,'short_description':'".($ticket->subject ? $ticket->subject : 'N/A')."','description':'".($ticket->info->description ? $ticket->info->description : 'N/A')."','opened_by':'srvsnowStrataPD','assignment_group':'Stratacache','contact_type':'Phone','incident_state':2, 'work_notes':NULL,'u_comments':NULL,}";


            $testdata = '{"result":{"Output":"{\"result\":{\"short_description\":\"Testing 123\",\"close_code\":\"\",\"assignment_group\":\"\",\"caused_by\":\"\",\"description\":\"Testing 456\",\"u_error_sub_category\":\"\",\"close_notes\":\"\",\"number\":\"INC11832352\",\"contact_type\":\"phone\",\"incident_state\":\"1\",\"urgency\":\"4\",\"opened_by\":\"a5bd3ce11bb17854b3c60ed3604bcbd4\",\"u_issue\":\"\",\"assigned_to\":\"\",\"comments\":\"\",\"cmdb_ci\":\"32c4c7b31b8a58902ac554e56e4bcbc1\",\"u_environment\":\"Production\",\"impact\":\"4\",\"u_hnb_subcat\":\"\",\"u_hnb_app_issues\":\"Functionality\",\"u_error_categorization\":\"\",\"caller_id\":\"\",\"work_notes\":\"\",\"subcategory\":\"\",\"category\":\"Application Services\"}}","notfoundfields":null,"TotalCount":null,"Status":"Success","error":null}}';

            $testdata = json_decode($testdata);

            $testdataoutput = json_decode($testdata->result->Output);

            print_r($testdataoutput->result->number);

            exit();

            Error Category => Required when resolved
            Error Subcategory => Required when resolved
            Close Code => Required when resolved
            Close Notes => Required when resolved
            */


            try {
                if ($ticket->servicenowID == NULL) {
                    $response = $this->postInsert($ticket);
                } else {
                    $response = $this->putUpdate($ticket);
                }

                $testdata = json_decode($response);

                $testdataoutput = json_decode($testdata->result->Output);

                if ($ticket->servicenowID == NULL) {
                    $ticket->servicenowID = $testdataoutput->result->number;
                    if ($ticket->status == 'Closed') {
                        $ticket->push_to_servicenow = 1;
                    } else {
                        $ticket->push_to_servicenow = NULL;
                    }
                } else {
                    $ticket->push_to_servicenow = NULL;
                }
                $ticket->save();

                //print_r($testdataoutput);
                print "- " . $ticket->servicenowID . " Successfully updated\n";
            }
            catch (\Exception $e) {
                $scerror = new scerror;
                $scerror->script = basename(__FILE__, '.php');
                $scerror->error = "Failed (PUSH to ServiceNow) on record " . $ticket['requestID'] . "\n\n" . $e;
                $scerror->save();
            }

        }
    }

    private function postInsert($ticket) {
        $response = Http::withBasicAuth($this->hnb_user, $this->hnb_pw)->withHeaders(['Accept' => 'application/json'])->post($this->hnb_url.'api/thhnb/genericscriptedapi/genericpost?api_id='.$this->hnb_api, [
            'Opened for' => 'srvsnowStrataPD',
            'Category' => 'Application Services',
            'Subcategory' => 'E-Merchandising - Production',
            'Issue' => 'Functionality',
            'Configuration Item' => 'E-Merchandising - Production',
            'Environment' => 'Production',
            'Impact' => 4,
            'Urgency' => 4,
            'Short Description' => ($ticket->subject ? $ticket->subject : 'N/A'), //($ticket->subject ? $ticket->subject : 'N/A'),
            'Description' => ($ticket->info->description ? $ticket->info->description : 'N/A'), //($ticket->info->description ? $ticket->info->description : 'N/A'),
            'Opened By' => 'srvsnowStrataPD',
            //'Assignment Group' => 'Stratacache',
            'Reported Type' => 'Phone',
            //'Incident State' => 2, // (Active) 6 (Resolved)
            //'Work Notes' => NULL,
            //'Additional Comments' => NULL,
        ]);

        return $response;
    }

    private function putUpdate($ticket) {
        if ( $ticket->status == 'Closed' ) {
            $incstate = 6;
        } else {
            $incstate = 2;
        }

        $response = Http::withBasicAuth($this->hnb_user, $this->hnb_pw)->withHeaders(['Accept' => 'application/json'])->put($this->hnb_url.'api/thhnb/genericscriptedapi/genericput?api_id='.$this->hnb_api.'&table_record='.$ticket->servicenowID, [
            'Opened for' => 'srvsnowStrataPD',
            'Category' => 'Application Services',
            'Subcategory' => 'E-Merchandising - Production',
            'Issue' => 'Functionality',
            'Configuration Item' => 'E-Merchandising - Production',
            'Environment' => 'Production',
            'Impact' => 4,
            'Urgency' => 4,
            'Short Description' => ($ticket->subject ? $ticket->subject : 'N/A'), //($ticket->subject ? $ticket->subject : 'N/A'),
            'Description' => ($ticket->info->description ? $ticket->info->description : 'N/A'), //($ticket->info->description ? $ticket->info->description : 'N/A'),
            'Opened By' => 'srvsnowStrataPD',
            //'Assignment Group' => 'Stratacache',
            'Reported Type' => 'Phone',
            'Incident State' => $incstate, // (Active) 6 (Resolved)
            'Work Notes' => NULL,
            'Additional Comments' => NULL,
        ]);

        return $response;
    }
}
