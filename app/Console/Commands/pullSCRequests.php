<?php

namespace App\Console\Commands;

use App\Models\scerror;
use App\Models\screquest;
use App\Models\screquest_info;
use App\Models\screquest_note;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class pullSCRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stratacache:pullSCRequests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $sc_url;
    private $sc_api;
    private $sc_bu;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->sc_url = env('STRATA_DOMAIN');
        $this->sc_api = env('STRATA_API_KEY');
        $this->sc_bu = env('STRATA_BUSINESS_UNIT');
    }

    private function saveTicketInfo($ticketid, $ticketreqid, $ticketinfo) {
        //print $ticketid . " - " . $ticketreqid . "\n";
        //print_r($ticketinfo);
        //exit();

        $ticketinfo = $ticketinfo[0];

        $ticketInfoDelete = screquest_info::where('screquest_id', $ticketid)->delete();

        $screquest2 = new screquest_info;

        $screquest2->requestID = $ticketreqid;
        $screquest2->description = $ticketinfo['description'];
        $screquest2->priority = $ticketinfo['priority'];
        $screquest2->status = $ticketinfo['status'];
        $screquest2->category = $ticketinfo['category'];
        $screquest2->createdBy = $ticketinfo['createdBy'];
        $screquest2->subject = $ticketinfo['subject'];
        $screquest2->screquest_id = $ticketid;

        $screquest2->save();

    }

    private function saveTicketNotes($ticketid, $ticketreqid, $ticketnotes) {
        //print $ticketid . " - " . $ticketreqid . "\n";
        //print_r($ticketnotes);

        $ticketNoteDelete = screquest_note::where('screquest_id', $ticketid)->delete();

        foreach ($ticketnotes as $ticketnote) {
            //print_r($ticketnote);

            $noteid = 0;
            $ispublic = 'false';

            if (array_key_exists('notifID', $ticketnote)) { $noteid = $ticketnote['notifID']; }
            if (array_key_exists('noteID', $ticketnote)) { $noteid = $ticketnote['noteID']; }
            if (array_key_exists('isPublic', $ticketnote)) { $ispublic = $ticketnote['isPublic']; }

            $ticketNoteDate = date( "Y-m-d H:i:s", strtotime($ticketnote['notifDate']));

            $screquest3 = new screquest_note;

            $screquest3->type = $ticketnote['notifyType'];
            $screquest3->description = $ticketnote['notifDesc'];
            $screquest3->noteID = $noteid;
            $screquest3->date = $ticketNoteDate;
            $screquest3->actUser = $ticketnote['recentActUser'];
            $screquest3->isPublic = $ispublic;
            $screquest3->requestID = $ticketreqid;
            $screquest3->screquest_id = $ticketid;

            $screquest3->save();

        }

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $response = Http::get($this->sc_url . 'api/json/getRequestsByView', [
                'apikey' => $this->sc_api,
                'businessUnit' => $this->sc_bu,
            ]);

            $content = $response->json();//$response->json();
            //$data = json_encode( $content );
            //$content = str_replace("[","",$content);
            //$content = str_replace("]","",$content);

            //$data = json_decode($data, true);
            foreach ($content['response']['result']['requests']['request'] as $ticket) {
                //print_r($ticket);
                //print "\n\n";
                $savenotes = 0;
                $saveinfo = 0;

                try {
                    $response2 = Http::get($this->sc_url . 'api/json/getRequest', [
                        'apikey' => $this->sc_api,
                        'id' => $ticket['requestID'],
                    ]);
                    $content2 = $response2->json();
                    if ($this->findKey($content2, 'requests')) {
                        $ticketinfo = $content2['response']['result']['requests']['request'];
                        $saveinfo = 1;
                    }

                    $response3 = Http::get($this->sc_url . 'api/json/getConversations', [
                        'apikey' => $this->sc_api,
                        'id' => $ticket['requestID'],
                    ]);
                    $content3 = $response3->json();
                    if ($this->findKey($content3, 'requests')) {
                        $ticketnotes = $content3['response']['result']['requests']['request'];
                        $savenotes = 1;
                    }


                    print "Ticket - " . $ticket['requestID'] . "\n";
                    //print $ticketinfo[0]['description'] . "\n";

                    $ticketchk = screquest::where('requestID', '=', $ticket['requestID'])->first();
                    //print_r($ticketchk);

                    $updflag = NULL;

                    $ticketdateformat = date("Y-m-d H:i:s", strtotime($ticket['createdTime']));
                    $ticketdateformat2 = date("Y-m-d H:i:s", strtotime($ticket['updatedTime']));

                    $launchdate = date("Y-m-d H:i:s", strtotime('2021-10-16'));


                    if ($ticketchk == NULL) {
                        $screquest = new screquest;

                        if ($ticketdateformat >= $launchdate) {
                            $updflag = 1;
                        }
                    } else {
                        $screquest = $ticketchk;
                    }

//                $screquest = new screquest;
                    if ($ticketchk) {
                        //print $ticketdateformat2 . " - " . $ticketchk['updated_in_system'] . "\n";
                        //print $ticket['status'] . " - " . $ticketchk['status'] . "\n";
                        if (($ticketdateformat2 > $ticketchk['updated_in_system'] || $ticket['status'] != $ticketchk['status']) && $ticketchk != NULL) {
                            //print $ticketdateformat2 . " - " . $ticketchk['updated_in_system'] . "\n";
                            //print "Yes\n";
                            $updflag = 1;
                        }
                    }

/*
                    if ($updflag == 1) {
                        $screquest->status = $ticket['status'];
                        $screquest->subject = $ticket['subject'];
                        $screquest->supportRep = $ticket['supportRep'];
                        $screquest->contact = $ticket['contact'];
                        $screquest->statusID = $ticket['statusID'];
                        $screquest->requestID = $ticket['requestID'];
                        $screquest->created_in_system = $ticketdateformat;
                        $screquest->updated_in_system = $ticketdateformat2;
                        $screquest->push_to_servicenow = 1;

                        $screquest->save();

                        if ($ticketchk == NULL) {
                            $insertid = $screquest->id;
                        } else {
                            $insertid = $ticketchk->id;
                        }

                        //print $insertid . "\n";
                    }

                    //$insertid = 1111;

                    //print_r($ticketinfo);
                    if ($saveinfo == 1) {
                        $this->saveTicketInfo($insertid, $ticket['requestID'], $ticketinfo);
                    }
                    if ($savenotes == 1) {
                        $this->saveTicketNotes($insertid, $ticket['requestID'], $ticketnotes);
                    }
*/

//                print $ticket['contact'] . "\n";
                }
                catch (\Exception $e) {
                    $scerror = new scerror;
                    $scerror->script = basename(__FILE__, '.php');
                    $scerror->error = "Failed on record " . $ticket['requestID'] . "\n\n" . $e;
                    $scerror->save();
                }
            }

//            $ticketinfochk =

            print "\n\n";
            //var_dump($content['response']['result']['requests']['request']);//->results[0];
        }
        catch(\Exception $e) {
            $scerror = new scerror;
            $scerror->script = basename(__FILE__, '.php');
            $scerror->error = "Could not execute API call";
            $scerror->save();
        }
    }

    private function findKey($array, $keySearch)
    {
        foreach ($array as $key => $item) {
            if ($key == $keySearch) {
                //echo 'yes, it exists';
                return true;
            } elseif (is_array($item) && $this->findKey($item, $keySearch)) {
                return true;
            }
        }
        return false;
    }
}
