<?php
if(!defined('__MOYIZA__')) exit;

class Job{

    public static $total  =   0;

    /**
     * get job list from engine
     *
     * @param array $options
     * @return array
     */
    public static function jobList($param){

        $page   =  (!empty($param['page']) && is_numeric($param['page']) && ($param['page']>1)) ? $param['page'] : 1;
        $per_page =  (!empty($param['per_page']) && is_numeric($param['per_page']) && ($param['per_page']>0)) ? $param['per_page'] : 20;

        // 조건이 있는지 체크 , 특수조건이 없으면 그냥 db에서
        if(!empty($param['db']) && !array_diff(array_keys($param),array('db','page','per_page','field'))){
            $db  =   new Db;
            $field =  !empty($param['field']) ? $param['field'] : '*';
            $total =  $db->table('job_guin')->where(array("guin_end_date >= curdate() OR guin_choongwon='1'"))->count();
            $limit_start =   ($page-1) * $per_page;
            $data_list =  $db->table('job_guin')
                ->field($field)
                ->where(array("guin_end_date >= curdate() OR guin_choongwon='1'"))
                ->where('guin_wait=0')
                ->order('guin_date desc')
                ->limit($limit_start,$per_page)
                ->select();
            return array('total'=>$total,'list'=>$data_list);
        }
        
        $engine =   new Sphinx('job');
        $engine->filter('guin_wait',0);  // 유료채용정보인에 결제완료 안했을시

        // 정렬
        if(!empty($param['order']) && in_array($param['order'],array(
            'title','title_desc',
            'career','career_desc',
            'gdate','mdate','edate'
        ))){
            if($param['order']=='title'){
                $engine->order('guin_title','asc');
            }else if($param['order']=='title_desc'){
                $engine->order('guin_title','desc');
            }else if($param['order']=='career'){
                $engine->order('guin_career_order','asc');
            }else if($param['order']=='career_desc'){
                $engine->order('guin_career_order','desc');
            }else if($param['order']=='gdate'){
                $engine->order('gdate','desc');
            }else if($param['order']=='mdate'){
                $engine->order('mdate','desc');
            }else if($param['order']=='edate'){
                $engine->order('edate','desc');
            }
        }else{
            $engine->order('gdate','desc');
        }
        
        // 마감일
        $end_time   =   strtotime(date('Ymd')) + 86400; // 오늘 마감 초수
        if(!empty($param['expire']) && in_array($param['expire'],array('today','1','2','3','ever'))){
            switch($param['expire']){
                case 'today':
                    $engine->filter('guin_choongwon',0);
                    $engine->range('edate',0,$end_time);
                    break;
                case '1':
                case '2':
                case '3':
                    $end_time  += ( (int)$param['expire'] * 86400 );  // 일간만큼 초수를 추가한다
                    $engine->filter('guin_choongwon',0);
                    $engine->range('edate',0,$end_time);
                    break;
                case 'ever':
                    $engine->filter('guin_choongwon',1);
                    break;
            }
        }else{
            $end_time  =  time();
            $engine->range('edate',$end_time,9999999999);
        }

        // 페이징

        $limit_start =   ($page-1) * $per_page;
        $engine->limit ( $limit_start , $per_page );

        $query  =   array();

        // 업종
        if(!empty($param['type1']) && is_numeric($param['type1'])){
            if(!empty($param['type2']) && is_numeric($param['type2'])){
                $query[]   =   '@type "아일'.$param['type1'].'아이'.$param['type2'].'아"';
            }else{
                $query[]   =   '@type "아일'.$param['type1'].'아"';
            }
        }

        // 구직종류
        if(!empty($param['guin_type'])){
            $query[]   =   '@guin_type "'.$param['guin_type'].'"';
        }

        // 지역
        if(!empty($param['country']) && is_numeric($param['country'])){
            if(!empty($param['province']) && is_numeric($param['province'])){
                if(!empty($param['city']) && is_numeric($param['city'])){
                    $query[]   =   '@region "아국'.$param['country'].'아성'.$param['province'].'아시'.$param['city'].'아"';
                }else{
                    $query[]   =   '@region "아국'.$param['country'].'아성'.$param['province'].'아"';
                }
            }else{
                $query[]   =   '@region "아국'.$param['country'].'아"';
            }
        }

        // 직급
        if(!empty($param['guin_grade'])){
            $query[]   =   '@guin_grade "'.$param['guin_grade'].'"';
        }

        // 학력
        if(!empty($param['guin_edu'])){
            $query[]   =   '@guin_edu "'.$param['guin_edu'].'"';
        }


        // 경력 신입,1년,2년,3년,15년 , 앞에수만 인식
        if(!empty($param['guin_career'])){
            if($param['guin_career']=='신입'){
                $query[]   =   '@guin_career "신입"';
            }else{
                $year = (int)$param['guin_career'];
                $query[]   =   '@guin_career "아'. $year.'아"';
            }
        }

        // 경력직만
        if(!empty($param['guin_career_have'])){
            $engine->filter('guin_career_have',1);
        }

        // 검색어
        if(!empty($param['keyword'])){
            array_unshift($query,('"'.$param['keyword'].'"'));
        }

        // 우대등록 , 기간별이라는 전제하에 한다
        $now_days    =  $_ENV['setting']['days']+1;
        if(!empty($param['woodae'])){
            $engine->range('woodae',(int)$now_days, 99999);
        }
        // 프리미엄
        if(!empty($param['premium'])){
            $engine->range('premium',(int)$now_days, 99999);
        }
        // 스페셜
        if(!empty($param['special'])){
            $engine->range('special',(int)$now_days, 99999);
        }
        // 스피드
        if(!empty($param['speed'])){
            $engine->range('speed',(int)$now_days, 99999);
        }
        // 추천
        if(!empty($param['pick'])){
            $engine->range('pick',(int)$now_days, 99999);
        }

        // run
        if($query){
            $engine->query(implode(' ',$query));
        }

        // run
        $engine->run();
        
        // no result
        $result  =   $engine->result();
        if(!$result){
            return array();
        }

        $total =  $engine->total();

        // db검색을 통해서 필요한 필드를 더 가져온다
        $return_data = array();
        if(!empty($param['db']) && $param['db']=='1'){
            $no_array  =  array_keys($result);
            $return_data = array_fill_keys($no_array,array());
            $db  =   new Db;
            $field =  !empty($param['field']) ? $param['field'] : '*';
            $data_list =  $db->table('job_guin')->field($field)->where("number in (".implode(',',$no_array).")")->select();
            foreach($data_list as $data_info){
                $return_data[$data_info['number']]  =  $data_info;
            }
        }else{
            foreach($result as $no=>$value){
                $return_data[$no]  =   $value['attrs'];
            }
        }

        return $return_data;
    }

    function hello(){
        echo 'hello';
    }
}