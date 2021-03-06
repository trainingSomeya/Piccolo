<?php

App::uses('AppController', 'Controller');

class MusicController extends AppController {
    public function beforeFilter() {
        parent::beforeFilter();
        //$this->Auth->allow();
    }

    public function index() {
        $imageList = $this->getImage();
        $this->set(compact('imageList'));
        $this->layout = '';
    }
    
    public function note_edit($id){
        $outputDir = ROOT.DS.APP_DIR.'/tmp/files/image/'.$id.'/0/';
        
        $pipeDir = PIPE_ROOT_DIR.$id;
        $input_fpath = ROOT.DS.APP_DIR.'/tmp/files/image/'.$id.'/0/raw/'.$id.'.bmp';
        $output_fpath_prefix = $outputDir.$id;
        $inPipe = $pipeDir.'/input';
        if (!file_exists($pipeDir)){
            mkdir($pipeDir, 0777, true);
        }
        if (!file_exists($inPipe)){
            posix_mkfifo($inPipe, '0500');
            $exe = ROOT.DS.'bin/core_app '. "$inPipe" . ' ' ." > /dev/null &";
            $pi = fopen($inPipe, 'w+');
            fwrite($pi, "load_bmp ".$input_fpath."\n");
            fflush($pi);
            exec($exe);
            fclose($pi);
        } else {
            $pi = fopen($inPipe, 'w+');
            fwrite($pi, "load_bmp ".$input_fpath."\n");
            fflush($pi);
            fclose($pi);
        }
        $this->set('imageName', $id);
        $this->layout = '';
    }
    
    public function add()
    {
        if ($this->request->is('post')) {
            if ($this->Music->save($this->request->data)) {
                $id = $this->Music->getLastInsertID();
                $directoryPath = ROOT.DS.APP_DIR.'/tmp/files/image/'.$id;
                if (mkdir($directoryPath, 0777, true)) {
                    $this->imageConversion($id, $this->request->data['Music']['image']['name']);
                }
            }
            $this->Flash->error(__('Unable to save the Image.'));
        }
    }
    
    public function getImage($id = 0)
    {
        if ($id != 0) {
            $imageData = $this->Music->find('first',array(
                'conditions' => array(
                    'id' => $id,
                ),
            ));
            return $imageData;
        } else {
            $imageList = $this->Music->find('list', array(
                'fields' => array('id', 'image')
                )
            );
            return $imageList;
        }
    }
    
    public function imageConversion($id = 0, $fname = '')
    {
        $reFilename =  $id.'.bmp';
        $input_fpath = ROOT.DS.APP_DIR.'/tmp/origFiles/image/'.$id.'/'.$fname;
        $outputDir = ROOT.DS.APP_DIR.'/tmp/files/image/'.$id.'/0/raw/';
        $output_fpath = $outputDir.$reFilename;
        
        if (!file_exists($outputDir)){
            mkdir($outputDir, 0777, true);
        }
        
        $exe = 'convert '. "$input_fpath" . ' -type truecolor ' . "$output_fpath";

        $flg = exec($exe);
        
        if ($flg == 0) {
            $this->Flash->success(__('The Image has been saved and combined'));
            $this->redirect(array('action' => 'index'));
        } else {
            $this->Flash->error(__('Unable to combine the Image.'));
            $this->redirect(array('action' => 'index'));
        }
    }
}
