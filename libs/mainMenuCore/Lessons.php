<?php
	require_once "EducationObject.php";
	
	class Lesson extends EducationObject implements JsonSerializable, IEducationalObject
	{                
	
	    public function EducationObjectInfo($id) {
	
	    }
	    	    
	    function EditLessonConstruct($lessId, $theme, $lessName, $discription = '', $img = '')
	    {
	        $this->objectId = $lessId;
            $this->name = $lessName;
	        $this->discription = $discription;
	        $this->img = $img;
            $this->theme = $theme;
	    }
        
	    function GetLessonConstruct($result)
	    {
	    	$this->objectId = $result['lesson_id'];
	        $this->name	= $result['lessonName'];
	        $this->discription = $result['discription'];
	        $this->theme = $_GET['theme']; // Временно
	        $this->img = $result['img'];
	    }
	
	    public function initializeFields($currentTheme, $lessonName, $lessonDiscription = '', $lessonIMG = '') 
	    {	
	    	$this->name = $lessonName;
	    	$this->discription = $lessonDiscription;
	    	$this->img = $lessonIMG;
	    	$this->theme = $currentTheme;						
	    }
	    
	    public function Create()
	    {
			if (empty($this->name)) throw new Exception("Недопустимое название урока.");	

			//проверка на валидность картинки			
			if ($this->img['tmp_name'])
			{
				$ExtentionsClassificator = new extensionClassificator();
				$extention = pathinfo($this->img['name'], PATHINFO_EXTENSION);
				if ($ExtentionsClassificator->classificate($extention) != "pics") throw new Exception("Недопустимое расширение файла($extention)."); 
			}

			global $mysqli;

			$CurentDate = date('Y\.m\.d');

			//заносим новый урок в БД
			$mysqli->query(
			"INSERT INTO lessons (lesson_id, lessonName, theme_id_fk, datemade, lastModification, discription, img) 
			VALUES 
			(NULL, '$this->name', '$this->theme', '$CurentDate', '$CurentDate', '$this->discription', '".$this->img['name']."')"
			);
			$lastInsertId = $mysqli->insert_id;

			//Создать новую директорию темы
			mkdir("themes/theme_$this->theme/lesson_$lastInsertId");
                        
            $name = '';
                        
			if ($this->img['tmp_name'])
			{					
				$dir = "themes/theme_$this->theme/lesson_$lastInsertId"; // путь к каталогу загрузок на сервере			
				$name = basename($this->img['name']);//имя файла и расширение
				$file = "$dir/$name";//полный путь к файлу				

				if (!($success = move_uploaded_file($this->img['tmp_name'], $file))) throw new Exception("Ошибка перемещения файла.");
			}	
                        
         $this->img = $name;
         $this->objectId = $lastInsertId;
	    }

		public function Info($id)
		{
			$mysqli = $GLOBALS['mysqli'];

			$lessonInfo = $mysqli->query("
			SELECT lessonName, datemade, lastModification, discription, IFNULL(fileCount.files, 0) as fileCount
			FROM lessons LEFT JOIN
			(SELECT lesson_id_fk, COUNT(*) as files
				FROM lessonsFiles GROUP BY lesson_id_fk) as fileCount
			ON (lessons.lesson_id = fileCount.lesson_id_fk)
			WHERE lesson_id = $id
			");
			$this->discription = $mysqli->result($lessonInfo, 0,"discription");
			$this->name = $mysqli->result($lessonInfo, 0,"lessonName");
			$this->datemade = $mysqli->result($lessonInfo, 0,"datemade");
			$this->lastModification = $mysqli->result($lessonInfo, 0,"lastModification");
			$this->fileCount = $mysqli->result($lessonInfo, 0,"fileCount");
			$this->Discription = (empty($discription)) ? "нет" : $discription;
			$this->type = 1;
		}
        
	    public function getElement() {
			return include 'templates/LessonElement.html';
	    }    
        
        public function jsonSerialize()
	    {
			return array(
				'Caption' => $this->name,
				'Discription' => $this->Discription,
				'FileCount' => $this->fileCount,
				'Datemade' => $this->datemade,
				'LastModification' => $this->lastModification,
				'Type' => $this->type
			);
	    }
	} 
?>