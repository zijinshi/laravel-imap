<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Client;
use App\Models\Email;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'FetchEmails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Emails from service@zijinshi.net';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info("FetchEmails command called.");
        $oClient = new Client([
            'host'          => 'imap.exmail.qq.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => '*****@*****.net',
            'password'      => '*******',
            'protocol'      => 'imap'
        ]);
        /* Alternative by using the Facade
        $oClient = Webklex\IMAP\Facades\Client::account('default');
        */

        //Connect to the IMAP Server
        $oClient->connect();


        $oFolder = $oClient->getFolder("Sent Messages");

        $i = 587 * 50;

        for( $page_index = 588;; $page_index ++ )
        {
            $aMessage = $oFolder->query()->all()->limit( 50, $page_index )->get();
            if( ( !$aMessage ) || ( $aMessage->count() == 0 ) )
            {
                break;
            }

            foreach($aMessage as $oMessage){
                $i ++;
                echo $i." ";

                $toEmails = $oMessage->getTo();
                if( (!$toEmails) || ( count($toEmails) == 0 ) )
                {
                    $toEmails = "";
                    $email = "";
                }else
                {
                    $email = $toEmails[0]->mail;
                }

                $sent_date = $oMessage->getDate();
                if( ! $sent_date )
                {
                   $sent_date = "";
                }else
                {
                    $sent_date = $sent_date->toDateString();
                }
                $subject = $oMessage->getSubject();
                if( !$subject )
                {
                    $subject = "";
                }
                echo " 时间：" .  $sent_date ."  主题：".$subject. "  收件人：" .  $email ."\n";  // "\n"是换行，'\n'是字符。

                if( !$email )
                {
                    continue;
                }
                $email_model = Email::where( 'email', $email )->first();
                if( $email_model )
                {
                    $email_model->count = $email_model->count + 1;
                    $email_model->save();
                }else
                {
                    $email_model = new Email;
                    $email_model->email = $email;
                    $email_model->count = 1;
                    if(  $sent_date )
                    {
                        $email_model->start_at = $oMessage->getDate()->toDateTimeString();
                    }
                    $email_model->save();
                }
            }
        }

        $oClient->disconnect();
    }
}
