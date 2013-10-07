<?php
if (!defined('_PS_VERSION_'))
	exit;

class EventsBar extends Module {

    public function __construct(){
        $this->name = 'eventsbar';
        $this->tab = 'front_office_features';
        $this->version = 1.0;
        $this->author = 'Herizo Ludovic';
        $this->need_instance = 0;
        parent::__construct();

        $this->displayName = $this->l('Events Bar');
        $this->description = $this->l('A bar to display some events , promotions ..');
    }


    public function install(){
        //create the database table
        Db::getInstance()->execute('
        CREATE TABLE '._DB_PREFIX_.'eventsbar (id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(100), event TEXT, startdate DATE , enddate DATE)');
            //install and hook to displayTop directly
            return (parent::install() && $this->registerHook('Top'));
    }

    //this is the part displayed in front office of website
    public function hookTop($params){
        global $smarty;
        $events  = Db::getInstance()->executeS('
                SELECT * FROM '._DB_PREFIX_.'eventsbar WHERE startdate <= NOW() AND enddate >=NOW()
            ');
        
        //create a simple string from the array result of executeS()
        $eventstr = '';
        foreach ($events as $event){
            $eventstr .= '<strong>'.$event['title'].'</strong>: '. $event['event']. ' | ';
        }
        
        //create the smarty variable for the template file
        $smarty->assign('event', $eventstr);
        //loading the template file
        return $this->display(__FILE__, 'eventsbar.tpl');
    }


    public function uninstall(){
        //delete the table before uninstall.
        Db::getInstance()->execute('DROP TABLE '._DB_PREFIX_.'eventsbar');

        return parent::uninstall();
    }

    public function getContent(){
        //getContent() is the part displayed in admin/module/configure

        //load jQuery ui js file and css file
        $this->context->controller->addJS($this->_path.'jquery-ui.js');
        $this->context->controller->addCSS($this->_path.'jquery-ui.css');

        /** sendevent : add new event */
        if(Tools::isSubmit('sendevent')){
            if(Tools::isSubmit('events') && Tools::isSubmit('eventtitle')){


                //Tools::getValue() is used to get save content of $_POST vars
                //Tools::getValue($varname) replace $_POST['varname']

                $events = Tools::getValue('events');
                $title = Tools::getValue('eventtitle');
                $startdate = Tools::getValue('startdate');
                $enddate = Tools::getValue('enddate');


                Db::getInstance()->autoExecute( _DB_PREFIX_.'eventsbar', array(
                    'id'=> null,
                    'title'=> pSQL($title),
                    'event' => pSQL($events),
                    'startdate' => pSQL($startdate),
                    'enddate' => pSQL($enddate))
                , 'INSERT');

                /* autoexecute() is better than execute() . we can use pSQL() to have save datas
                    Db::getInstance()->execute('
                    INSERT INTO '._DB_PREFIX_.'eventsbar VALUES(null,"'.$title.'", "'.$events.'" ,"'.$startdate.'", "'.$enddate.'")
                    ');
                */
            }
        }


        //Tools::isSubmit() is better than isset($_POST[])

        if(Tools::isSubmit('deleteevent')){
            //handle delete of an event 
                $todelete = Tools::getValue('deleteevent');
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'eventsbar WHERE id='.$todelete);
        }

        //->executeS($sql) is same as execute($sql) but with array result return ..
        $events  = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'eventsbar');

        $output ='';
        $output .= '<div style="width:600px; margin:auto; padding:20px">
                    <h4>Events </h4>
                    <table class="table"  style="margin:auto">';
        $output .= '<thead><tr>
                            <th>Title</th>
                            <th>Event</th>
                            <th>Start date</th>
                            <th>End date </th>
                            <th>Delete</th>
                    </tr></thead>
                    ';
        foreach($events as $event){
            $output .='<tr class="row_hover">';
            $output .= '<td class="pointer center">'.$event['title'].'</td>';
            $output .= '<td class="pointer center">'.$event['event'].'</td>';
            $output .= '<td class="pointer center">'.$event['startdate'].'</td>';
            $output .= '<td class="pointer center">'.$event['enddate'].'</td>';
            $output .= '<td class="pointer center" >
                <form action="" method="POST" >
                    <input type="hidden" value="'.$event['id'].'" name="deleteevent"/>
                    <input type="submit" value="Delete"/>
                </form>
            </td></tr>';
        }
        //Tools::safeOutput($_SERVER['REQUEST_URI']) contain the actual url with all parametters
        $output .= '</table></div>';
        $output.= '
        <div style="width:350px; margin:auto; padding:20px">
        <form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="POST">
            <fieldset><legend> Add an Event </legend>
                <label style="width:100%; text-align:left">Title</label><br/><br/>
                <input type="text" name="eventtitle" required />
                <label style="width:100%; text-align:left">Text</label><br/><br/>
                <textarea name="events" cols="50" rows="5" required ></textarea></br/><br/>
                <label style="width:80px">Start date</label>
                <input type="text" name="startdate" class="datepicker" required /><br/><br/>
                <label style="width:80px">End date</label>
                <input type="text" name="enddate" class="datepicker" required /></br/><br/>
                <input type="submit" name="sendevent"/>
            </fieldset/>
        </form>
        <script type="text/javascript">
            $(".datepicker").datepicker({ dateFormat: "yy-mm-dd" });
        </script>
        </div>';

        return $output;
    }

}
