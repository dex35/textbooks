<?php
	require_once('mainMenu.php');

	/**
	* Класс управления главным меню для учителя
	*/
	class teacherMainMenu extends user implements IUser  
	{
		function __construct($teacherId)
		{
			$this->id = $teacherId;
			$mysqli = $GLOBALS['mysqli'];

			//Получаем доп. информацию о учителе
			$teacherInfo = $mysqli->query("SELECT fio FROM teachers WHERE teacher_id = $this->id");
			$this->fio = $mysqli->result($teacherInfo, 0);
		}

		function getMenu()
		{
			include "teacherMainMenu.html";
		}

		function getThemes()
		{
			global $mysqli;

			//выбрать все темы, где владелец этот учитель
			$teacherThemes = $mysqli->query("SELECT theme_id, themeName, discription, img FROM themes WHERE teacher_id_fk = ".$this->id);
			if(!mysqli_num_rows($teacherThemes)) return;

			while($theme = mysqli_fetch_array($teacherThemes))
			{//создать тему и вывести
                            $currentTheme = new Theme();
                            $currentTheme->GetThemeConstruct($theme);
                            echo $currentTheme->getElement();
			}
		}		

		function EditTheme($themeId, $themeName, $discription, $img )
		{
			//заменить параметры темы с id

			try 
			{
				if (empty($themeName)) throw new Exception("Недопустимое название темы.");
			
				global $mysqli;

				$name = $mysqli->result($mysqli->query("SELECT img FROM themes WHERE theme_id = $themeId"), 0);
				$dir = "themes/theme_$themeId"; // путь к каталогу загрузок на сервере
				$file = ($name) ? "$dir/$name" : "";//полный путь к файлу

				if ($img['tmp_name'])
				{
					$ExtentionsClassificator = new extensionClassificator();
					$extention = pathinfo($img['name'], PATHINFO_EXTENSION);
					if ($ExtentionsClassificator->classificate($extention) != "pics") throw new Exception("Недопустимое расширение файла($extention)."); 					
			
					$oldName = $name;
					$name = basename($img['name']);//имя файла и расширение
					$file = "$dir/$name";//полный путь к файлу				

					if (!($success = move_uploaded_file($img['tmp_name'], $file))) throw new Exception("Ошибка перемещения файла.");

					//замена = закачать -> удалить 	
					@unlink("themes/theme_$themeId/$oldName");

					$mysqli->query("UPDATE themes SET themeName = '$themeName', discription = '$discription', img = '".$img['name']."' WHERE theme_id = $themeId");

				}
				else
				if (empty($img))
				{
					//картинка не заменяется. запрос на апдейт
					$mysqli->query("UPDATE themes SET themeName = '$themeName', discription = '$discription' WHERE theme_id = $themeId");
				}
				else
				{
					//полное удаление изображения
					$mysqli->query("UPDATE themes SET themeName = '$themeName', discription = '$discription', img = '' WHERE theme_id = $themeId");					
					@unlink("themes/theme_$themeId/$name");
				}

				$this->jsOnResponse("{'type':'edit', 'message':'Тема изменена.', 'success':'1', 'themeId':'$themeId', 'themeName':`$themeName`, 'themeDiscription':`$discription`, 'themeIMG':'$file'}");
			
			} 
			catch (Exception $e) 
			{
				$this->jsOnResponse("{'message':'Ошибка изменения темы! ".$e->getMessage()."', 'success':'0'}");	
			}
			//Заменить все формы на аякс
			//https://youtu.be/qo7Hqwypwcc?list=PLtjuvkyFrt5Wjd-973N117XS7xuuoD6XM&t=1743			
		}

		/*                    --------Уроки и презентации--------                        */

		function getLessonsMenu()
		{
			include "teacherLessonsMenu.html";
		} 

	}
?>