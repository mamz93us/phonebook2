<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\PhoneRequestLog;
use Illuminate\Http\Request;

class PhonebookController extends Controller
{
    /**
     * Generate phonebook.xml for Grandstream UCM
     */
    public function generate(Request $request)
    {
        // Log requesting phone (IP, User-Agent, MAC, model)
        $ip        = $request->ip();
        $userAgent = $request->header('User-Agent', '');

        $mac   = null;
        $model = null;

        // Try to extract Grandstream model from User-Agent
        if (preg_match('/Grandstream\s+([A-Z0-9\-]+)/i', $userAgent, $m)) {
            $model = $m[1];
        }

        // Try to extract MAC address from User-Agent
        if (preg_match('/([0-9A-F]{2}(:[0-9A-F]{2}){5})/i', $userAgent, $m)) {
            $mac = strtoupper($m[1]);
        }

        PhoneRequestLog::create([
            'ip'         => $ip,
            'user_agent' => $userAgent,
            'mac'        => $mac,
            'model'      => $model,
        ]);

        // Build XML phonebook
        $xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xmlString .= "<AddressBook>\n";
        $xmlString .= "    <version>1</version>\n";

        foreach (Branch::orderBy('id')->get() as $branch) {
            $xmlString .= "    <pbgroup>\n";
            $xmlString .= "        <id>{$branch->id}</id>\n";
            $xmlString .= "        <name>{$branch->name}</name>\n";
            $xmlString .= "        <photos></photos>\n";
            $xmlString .= "        <ringtones></ringtones>\n";
            $xmlString .= "        <RingtoneIndex>0</RingtoneIndex>\n";
            $xmlString .= "    </pbgroup>\n";
        }

        foreach (Contact::orderBy('first_name')->get() as $c) {
            $fname = htmlspecialchars($c->first_name, ENT_XML1);
            $lname = htmlspecialchars($c->last_name ?? '', ENT_XML1);
            $email = htmlspecialchars($c->email ?? '', ENT_XML1);

            $xmlString .= "    <Contact>\n";
            $xmlString .= "        <id>{$c->id}</id>\n";
            $xmlString .= "        <FirstName>{$fname}</FirstName>\n";
            $xmlString .= "        <LastName>{$lname}</LastName>\n";
            $xmlString .= "        <Department></Department>\n";
            $xmlString .= "        <Primary>0</Primary>\n";
            $xmlString .= "        <Frequent>0</Frequent>\n";
            $xmlString .= "        <Phone type=\"Work\">\n";
            $xmlString .= "            <phonenumber>{$c->phone}</phonenumber>\n";
            $xmlString .= "            <accountindex>1</accountindex>\n";
            $xmlString .= "        </Phone>\n";
            $xmlString .= "        <Mail type=\"Work\">{$email}</Mail>\n";
            $xmlString .= "        <Group>{$c->branch_id}</Group>\n";
            $xmlString .= "        <PhotoUrl></PhotoUrl>\n";
            $xmlString .= "        <RingtoneUrl></RingtoneUrl>\n";
            $xmlString .= "        <RingtoneIndex>0</RingtoneIndex>\n";
            $xmlString .= "    </Contact>\n";
        }

        $xmlString .= "</AddressBook>";

        return response($xmlString, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * XML Preview Page
     */
    public function preview()
    {
        $branches = Branch::orderBy('id')->get();
        $contacts = Contact::orderBy('first_name')->get();

        return view('admin.xml-preview', compact('branches', 'contacts'));
    }
}
