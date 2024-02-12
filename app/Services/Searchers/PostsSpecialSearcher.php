<?php

namespace App\Services\Searchers;

use App\Facades\Curl;
use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Collection;
use Ticketpark\HtmlPhpExcel\HtmlPhpExcel;

class PostsSpecialSearcher extends BlogSearcher
{
    const FACULTY_BIOS = [
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=67',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=461',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=687',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=992',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=908',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=116',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=905',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=943',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=1114',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=432',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=329',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=220',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=1102',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=46',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=165',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=723',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=908',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=256',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=850',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=37',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=64',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=208',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=276',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=37',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=1116',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=892',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=40',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=505',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=578',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=728',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=338',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=324',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=1169',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=211',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=379',
    ];

    const URL_CODES = [
        'https://www2.clarku.edu/departments/holocaust/' => '200',
        'http://www2.clarku.edu/idce' => '404',
        'http://www2.clarku.edu/departments/politicalscience/' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=67' => '200',
        'http://www2.clarku.edu/departments/marsh/' => '200',
        'http://www2.clarku.edu/commencement/' => '404',
        'https://www2.clarku.edu/departments/history/?_ga=2.44698532.1483531380.1517235484-1268586853.1456754440' => '200',
        'https://www2.clarku.edu/departments/clarkarts/studioart/people/' => '404',
        'https://www2.clarku.edu/departments/clarkarts/screen/people/' => '404',
        'https://www2.clarku.edu/departments/clarkarts/media-culture-arts/people/' => '404',
        'https://www2.clarku.edu/departments/english/' => '200',
        'http://www2.clarku.edu/departments/foreign/' => '200',
        'https://www2.clarku.edu/departments/clarkarts/' => '200',
        'http://www2.clarku.edu/departments/psychology/' => '200',
        'http://www2.clarku.edu/research/mosakowskiinstitute/leadership/' => '404',
        'http://www2.clarku.edu/research/mosakowskiinstitute/' => '404',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=461' => '200',
        'https://www2.clarku.edu/departments/biology/' => '200',
        'http://www2.clarku.edu/departments/ie/' => '200',
        'http://www2.clarku.edu/departments/holocaust/' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=687' => '200',
        'http://www2.clarku.edu/departments/Psychology/' => '200',
        'https://www2.clarku.edu/departments/chemistry/' => '200',
        'https://www2.clarku.edu/departments/biochemistry/' => '200',
        'https://www2.clarku.edu/departments/sociology/' => '200',
        'http://www2.clarku.edu/departments/geography/' => '200',
        'http://www2.clarku.edu/clark-poll-emerging-adults/' => '200',
        'http://www2.clarku.edu/clark-poll-emerging-adults/info-graphic.cfm' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=992' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=908' => '200',
        'http://www2.clarku.edu/departments/biology/phd/index.cfm' => '404',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=806' => '404',
        'https://www2.clarku.edu/departments/history/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=116' => '200',
        'http://www2.clarku.edu/departments/economics/' => '200',
        'https://www2.clarku.edu/departments/international-development/' => '200',
        'http://www2.clarku.edu/schiltkampgallery/' => '404',
        'https://www2.clarku.edu/offices/career/' => '404',
        'http://www2.clarku.edu/departments/english/' => '200',
        'https://www2.clarku.edu/departments/geography/' => '200',
        'http://www2.clarku.edu/departments/es/' => '200',
        'http://www2.clarku.edu/departments/clarkarts/' => '200',
        'https://www2.clarku.edu/offices/career/internships/recent.cfm' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=905' => '200',
        'http://www2.clarku.edu/departments/biology/facultybio.cfm?id=52&amp;progid=4' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=943' => '200',
        'https://www2.clarku.edu/departments/womensstudies/' => '200',
        'http://www2.clarku.edu/departments/biology/' => '200',
        'https://www2.clarku.edu/departments/psychology/' => '200',
        'http://www2.clarku.edu/~psydept/' => '500',
        'http://www2.clarku.edu/departments/education/' => '200',
        'http://www2.clarku.edu/departments/history/' => '200',
        'https://www2.clarku.edu/departments/lawandsociety/' => '200',
        'https://www2.clarku.edu/departments/urban/' => '200',
        'https://www2.clarku.edu/departments/politicalscience/' => '200',
        'https://www2.clarku.edu/departments/ancientciv/' => '200',
        'http://www2.clarku.edu/departments/marsh/?_ga=2.63478118.819428084.1519658435-1268586853.1456754440' => '200',
        'http://www2.clarku.edu/departments/international-development-community-environment/' => '200',
        'http://www2.clarku.edu/difficultdialogues/' => '200',
        'http://www2.clarku.edu/departments/cc/undergraduate/' => '404',
        'http://www2.clarku.edu/departments/womensstudies/' => '200',
        'https://www2.clarku.edu/departments/clarkarts/theater/people/' => '404',
        'http://www2.clarku.edu/research_asd_ff.cfm' => '404',
        'https://www2.clarku.edu/departments/mathcs/people/csfaculty.cfm' => '404',
        'https://www2.clarku.edu/departments/mathcs/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=806' => '404',
        'http://www2.clarku.edu/departments/clarkarts/studioart/people/' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=1114' => '200',
        'https://www2.clarku.edu/departments/foreign/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=432' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=329' => '200',
        'https://www2.clarku.edu/departments/biology/people/grads.cfm' => '404',
        'https://www2.clarku.edu/departments/biology/phd/index.cfm' => '404',
        'https://www2.clarku.edu/departments/philosophy/' => '200',
        'http://www2.clarku.edu/departments/clarkarts/facilities/' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=220' => '200',
        'http://www2.clarku.edu/departments/chemistry/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=1102' => '200',
        'http://www2.clarku.edu/departments/geography/graduate/current-students/' => '404',
        'http://www2.clarku.edu/departments/womensstudies/akog/' => '404',
        'http://www2.clarku.edu/gsom/facultybio.cfm?id=519' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=46' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=165' => '200',
        'http://www2.clarku.edu/research/mosakowskiinstitute/leadership/index.cfm' => '404',
        'http://www2.clarku.edu/research/mosakowskiinstitute/leadership/john-obrien.cfm' => '404',
        'http://www2.clarku.edu/departments/holocaust/events/documents/OctoberProgram-FINAL.pdf' => '404',
        'https://www2.clarku.edu/gsom/facultybio.cfm?id=748&amp;progid=20' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=461' => '200',
        'https://www2.clarku.edu/departments/education/upcs/' => '404',
        'http://www2.clarku.edu/departments/clarkarts/?_ga=2.159356538.1198290049.1523892263-996118318.1521665993' => '200',
        'http://www2.clarku.edu/departments/iss/' => '200',
        'http://www2.clarku.edu/school-of-professional-studies/esl/' => '404',
        'http://www2.clarku.edu/departments/ancientciv/?_ga=2.145043126.308239699.1506950702-1268586853.1456754440' => '200',
        'http://www2.clarku.edu/research/summer-research/' => '500',
        'http://www2.clarku.edu/departments/biochemistry/' => '200',
        'http://www2.clarku.edu/departments/english/documents/16Poetry2.pdf' => '404',
        'http://www2.clarku.edu/departments/mathcs/faculty.cfm' => '404',
        'http://www2.clarku.edu/departments/PoliticalScience/' => '200',
        'https://www2.clarku.edu/departments/education/' => '200',
        'http://www2.clarku.edu/departments/geography/cvs/RoyChowdhury-CV-APR2017.pdf' => '404',
        'http://www2.clarku.edu/departments/sociology/' => '200',
        'http://www2.clarku.edu/departments/mathcs/' => '200',
        'https://www2.clarku.edu/departments/biology/facultybio.cfm?id=352&amp;progid=4&amp;' => '404',
        'https://www2.clarku.edu/departments/biology/facultybio.cfm?id=361&amp;progid=4&amp;' => '404',
        'http://www2.clarku.edu/faculty/jthackeray/index.html' => '200',
        'http://www2.clarku.edu/faculty/dlarochelle/index.html' => '200',
        'http://www2.clarku.edu/faculty/pbergmann/' => '200',
        'https://www2.clarku.edu/departments/mathcs/facultybio.cfm?id=777&amp;progid=21&amp;' => '404',
        'http://www2.clarku.edu/departments/cc/' => '200',
        'http://www2.clarku.edu/school-of-professional-studies/faculty.cfm' => '404',
        'http://www2.clarku.edu/departments/education/upcs/' => '404',
        'http://www2.clarku.edu/departments/race/' => '200',
        'http://www2.clarku.edu/departments/hero/' => '200',
        'http://www2.clarku.edu/departments/hero/HeroStakeholdersPresentation_2014.mp4' => '404',
        'http://www2.clarku.edu/departments/mathcs/facultybio.cfm?id=438&amp;progid=21' => '404',
        'http://www2.clarku.edu/departments/biology/facultybio.cfm?id=985' => '404',
        'http://www2.clarku.edu/undergraduate-admissions/fast-facts-rankings/rankings.cfm' => '404',
        'http://www2.clarku.edu/departments/africana-studies/' => '404',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=723' => '200',
        'http://www2.clarku.edu/financial-aid/scholarships/' => '404',
        'https://www2.clarku.edu/departments/ie/' => '200',
        'http://www2.clarku.edu/graduate-admissions/pdfs/faculty-resolution-regarding-executive-order-clark-university.pdf' => '404',
        'http://www2.clarku.edu/departments/geography/graduate/prospective-students/' => '404',
        'http://www2.clarku.edu/departments/urban/' => '200',
        'https://www2.clarku.edu/departments/race/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=908' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=256' => '200',
        'http://www2.clarku.edu/school-of-professional-studies/' => '404',
        'http://www2.clarku.edu/offices/leir/' => '410',
        'http://www2.clarku.edu/offices/leir/about.cfm' => '410',
        'http://www2.clarku.edu/graduate-admissions/visit/southborough-drop-in.cfm' => '404',
        'https://www2.clarku.edu/offices/its/' => '200',
        'https://www2.clarku.edu/gsom/facultybio.cfm?id=852&amp;progid=20' => '404',
        'https://www2.clarku.edu/departments/biology/faculty/cv/drewell.pdf' => '200',
        'http://www2.clarku.edu/departments/public-health/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=850' => '200',
        'http://www2.clarku.edu/departments/lawandsociety/' => '200',
        'https://www2.clarku.edu/offices/career/internships/types.cfm' => '404',
        'http://www2.clarku.edu/faculty/mmalsky/composer/' => '200',
        'https://www2.clarku.edu/departments/biology/facultybio.cfm?id=578' => '404',
        'https://www2.clarku.edu/departments/biology/facultybio.cfm?id=315&amp;progid=4&amp;' => '404',
        'http://www2.clarku.edu/departments/international-development/' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=37' => '200',
        'http://www2.clarku.edu/departments/biology/facultybio.cfm?id=578&amp;progid=4&amp;' => '404',
        'http://www2.clarku.edu/departments/mathcs/people/facultybio.cfm?id=447&amp;progid=8&amp;' => '404',
        'http://www2.clarku.edu/departments/mathcs/facultybio.cfm?id=311&amp;progid=21' => '404',
        'http://www2.clarku.edu/departments/asianstudies/' => '200',
        'http://www2.clarku.edu/departments/ancientciv/' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=64' => '200',
        'http://www2.clarku.edu/departments/latinamerica/' => '200',
        'https://www2.clarku.edu/offices/its/contact.cfm' => '404',
        'http://www2.clarku.edu/its/' => '200',
        'https://www2.clarku.edu/faculty/pbergmann/PJB_MasterPeople.html' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=208' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=276' => '200',
        'http://www2.clarku.edu/faculty/dhibbett/' => '200',
        'http://www2.clarku.edu/departments/english/news/english_times_files/archive/Sep08WN.pdf' => '404',
        'http://www2.clarku.edu/departments/clarkarts/music/people/' => '404',
        'http://www2.clarku.edu/faculty/dhines/family_impact_seminars.htm' => '200',
        'http://www2.clarku.edu/departments/geography/cvs/Angel_12_09.pdf' => '404',
        'http://www2.clarku.edu/clarkpoll/' => '404',
        'http://www2.clarku.edu/offices/marcom/' => '500',
        'http://www2.clarku.edu/departments/biology/facultybio.cfm?id=37&amp;progid=4' => '404',
        'http://www2.clarku.edu/departments/biology/facultybio.cfm?id=355&amp;progid=4' => '404',
        'http://www2.clarku.edu/departments/biochemistry/undergraduate/' => '404',
        'http://www2.clarku.edu/financial-aid/' => '404',
        'http://www2.clarku.edu/departments/es/ess/' => '404',
        'http://www2.clarku.edu/offices/leir/mayterm/general.cfm' => '410',
        'http://www2.clarku.edu/alumnienewsletter/2010_DEC.htm' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=37' => '200',
        'http://www2.clarku.edu/inauguration/' => '404',
        'http://www2.clarku.edu/inauguration/symposium/index.cfm' => '404',
        'http://www2.clarku.edu/inauguration/about/bio.cfm' => '404',
        'http://www2.clarku.edu/departments/geography/cvs/CPolsky_CV.pdf' => '404',
        'http://www2.clarku.edu/inauguration/symposium/sustainability.cfm' => '404',
        'http://www2.clarku.edu/inauguration/speech.cfm' => '404',
        'http://www2.clarku.edu/difficultdialogues_2.cfm' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=1116' => '200',
        'http://www2.clarku.edu/departments/computationalsci/' => '200',
        'https://www2.clarku.edu/departments/holocaust/conferences/informed/' => '404',
        'http://www2.clarku.edu/offices/president' => '404',
        'https://www2.clarku.edu/departments/biology/people/staff.cfm' => '404',
        'https://www2.clarku.edu/departments/biology/research/facilities.cfm' => '404',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=256' => '200',
        'https://www2.clarku.edu/departments/biology/undergraduate/prehealth.cfm' => '404',
        'https://www2.clarku.edu/departments/economics/' => '200',
        'https://www2.clarku.edu/financial-aid/scholarships/' => '404',
        'http://www2.clarku.edu/graduate/prospective/fifthyear/' => '404',
        'https://www2.clarku.edu/departments/prehealth/' => '200',
        'http://www2.clarku.edu/clark-poll-emerging-adults/?_ga=2.3778101.404777268.1533560589-1186485921.1533131449' => '200',
        'https://www2.clarku.edu/clark-poll-emerging-adults/' => '200',
        'https://www2.clarku.edu/research_asd_ff.cfm' => '404',
        'https://www2.clarku.edu/financial-aid/scholarships/leep-scholarship.cfm' => '404',
        'https://www2.clarku.edu/departments/marsh/' => '200',
        'https://www2.clarku.edu/departments/asianstudies/' => '200',
        'https://www2.clarku.edu/departments/peacestudies/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=226&amp;progid=4' => '500',
        'http://www2.clarku.edu/departments/philosophy/' => '200',
        'https://www2.clarku.edu/level2/research/FallFest2018.pdf' => '404',
        'https://www2.clarku.edu/offices/leir/internship.cfm' => '410',
        'http://www2.clarku.edu/departments/mathcs/student-honors-awards.cfm' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=578&amp;progid=4' => '500',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=723&amp;progid=14' => '500',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=892' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=40' => '200',
        'http://www2.clarku.edu/offices/career/?_ga=2.156161789.733023977.1548706305-570835543.1542743777' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=723' => '200',
        'https://www2.clarku.edu/faculty/dhibbett/' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=461&amp;progid=16' => '500',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=505' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=687' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=578' => '200',
        'http://www2.clarku.edu/departments/es/ecb/' => '404',
        'http://www2.clarku.edu/research_asd_ff.cfm?_ga=2.267655630.1919049675.1557154866-570835543.1542743777' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=728' => '200',
        'https://www2.clarku.edu/offices/career' => '404',
        'https://www2.clarku.edu/offices/leir/' => '410',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=338' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=324' => '200',
        'http://www2.clarku.edu/departments/hero/#2019' => '200',
        'http://www2.clarku.edu/faculty/facultybio.cfm?id=1169' => '200',
        'https://www2.clarku.edu/offices/leir/conferences.cfm' => '410',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=208' => '200',
        'https://www2.clarku.edu/gsom' => '404',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=116&amp;_ga=2.101111561.927268852.1585580612-570835543.1542743777' => '500',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=37&amp;_ga=2.197360492.271126477.1589228895-570835543.1542743777' => '500',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=578&amp;_ga=2.240800576.271126477.1589228895-570835543.1542743777' => '500',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=211' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=379' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=578&amp;_ga=2.262480460.1346621468.1617629857-2017323535.1617296683' => '500',
        'https://www2.clarku.edu/www2.clarku.edu/departments/International-Development-Community-Environment' => '404',
        'https://www.google.com/url?client=internal-element-cse&amp;cx=008285272570661257438:td1wgria-u4&amp;q=https://www2.clarku.edu/faculty/facultybio.cfm%3Fid%3D1193&amp;sa=U&amp;ved=2ahUKEwiYpbf5p7r3AhUipnIEHcmmC7UQFnoECAcQAQ&amp;usg=AOvVaw0THwY9p3LyAjmb3JmxfO0n' => '200',
        'https://www2.clarku.edu/faculty/facultybio.cfm?id=1173&amp;progid=8' => '500',
        'https://www2.clarku.edu/faculty/facultybio.cfm' => '200',
        'https://www2.clarku.edu/departments/womensstudies/images/technologies-of-resistence.jpg' => '200',
        'https://www2.clarku.edu/departments/womensstudies/images/Kate-Rushin-Flyer-February-6.jpg' => '200',
        'https://www2.clarku.edu/departments/physics/news/images/apscuwipAmherst.jpg' => '200',
        'https://www2.clarku.edu/departments/physics/news/documents/N.Halpern_Colloquium_10.2.19.pdf' => '200',
        'https://www2.clarku.edu/departments/physics/news/documents/I.Zeljkovic_Colloquium9.25.19.pdf' => '200',
        'https://alumni.clarku.edu/page.redir?target=https%3a%2f%2fwww2.clarku.edu%2ffaculty%2ffacultybio.cfm%3fid%3d685&amp;srcid=185266&amp;srctid=1&amp;erid=14111330&am'
    ];

    protected array $headers = [
        'ID',
        'Post',
        'Page',
        'Title',
        'Content',
        'Created',
    ];

    protected ?Collection $externalLinks = null;

    public function process(string $blogId, string $blogUrl): bool
    {
//        $linksTxt = Storage::path('www2_no_redirect.txt');
//        $links = explode("\n", file_get_contents($linksTxt));

        $this->externalLinks = collect(self::FACULTY_BIOS);

        if (! Schema::hasTable('wp_' . $blogId. '_posts')) {
            return false;
        }

        $foundSomething = false;

        $posts = (new Post())->setTable('wp_' . $blogId . '_posts')
            ->where('post_status', 'publish')
            ->orderBy('ID');

        $posts->each(function (Post $post) use ($blogUrl, $blogId, &$foundSomething) {
            $foundTitle = $this->wasFound($post->post_title);
            $foundContent = $this->wasFound($post->post_content);
            if ($foundContent || $foundTitle) {
                $foundSomething = true;
                $this->found->push([
                    'blog_id' => $blogId,
                    'blog_url' => $blogUrl,
                    'post_id' => $post->ID,
                    'post_name' => $post->post_name,
                    'title' => $post->post_title,
                    'date' => $post->post_date,
                    'content' => trim($post->post_content),
                ]);
            }
        });

        return $foundSomething;
    }

    public function render(): string
    {
        $html = '';

        $this->foundCount = 0;
        $html .= self::TABLE_TAG_START;
        $html .= $this->buildHeader();
        $this->found->each(function ($page) use (&$html) {
            $hasLinks = $this->grabLinks($page['content']) !== '';
            if (! $hasLinks) {
                return;
            }
            $url = $page['blog_url'] . $page['post_name'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= self::TABLE_CELL_CENTER;
            $html .= $page['blog_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $page['post_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->makeLink($url);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->highlight($page['title']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->grabLinks($page['content']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= Carbon::parse($page['date'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            if ($hasLinks) {
                $this->foundCount++;
            }
        });
        $html .= self::TABLE_TAG_END;

        $htmlPhpExcel = new HtmlPhpExcel($html);
        $htmlPhpExcel->process()->save('Faculty Bios Found.xlsx');

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
    }

    protected function grabLinks(string $content): string
    {
        $links = '';
        preg_match_all('/href=["\']?([^"\'>]+)["\']?/', $content, $matches);

        $matches = current($matches);
        collect($matches)->each(function ($match) use (&$links) {
            if (str_contains($match, 'www2.clarku.edu')) {
                $url = str_replace(['href="', '"'], '', $match);
                if ($this->externalLinks->contains($url)) {
                    $links .= '<a href="' . $url . '">' . $url . '</a><br>' . PHP_EOL;
                }
            }
        });

        return $links;
    }

    protected function error(): void
    {
        echo 'No search text specified. Syntax: ' . URL::to('/in_post') . '?text=';
    }
}
