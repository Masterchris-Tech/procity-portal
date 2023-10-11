<?php

    include "authorize.php";
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Origin: http://127.0.0.1:5173');

    
    $action = $_POST['action'];

    // If action iss fetch_post, then query database and fetch all posts
    if ($action == 'fetch_post'){

        // Build Query to Fetch all posts if the request is unauthenticated || authorized
        if ($authorized === false){
            $sqlQuery = "SELECT * FROM tbl_posts";
        }else{
            // If the request is authenticated || authorized, Filter the posts using user role
            // If the authenticated user role is admin, fetch all the posts
            if ($authorized_user['role'] == 'admin') {
                $sqlQuery = "SELECT * FROM tbl_posts";
            } else {
                $user_id = $authorized_user['id'];
                $sqlQuery = "SELECT * FROM tbl_posts WHERE user_id='$user_id'";
            }
        }

        if (isset($_POST['category']) && $_POST['category'] !== 'all') {
            $categoryId = getCategoryIdByname($_POST['category'], $conn);
            $sqlQuery = $sqlQuery . " WHERE category_id='" . $categoryId . "'";
        }

        if (isset($_POST['search_value']) && $_POST['search_value'] !== '') {
            $searchVal = $_POST['search_value'];

            // Check if it has search with category
            if (isset($_POST['category']) && $_POST['category'] !== 'all') {
                $sqlQuery = $sqlQuery . " AND title LIKE '%" . $searchVal . "%'";
            }else{
                $sqlQuery = $sqlQuery . " WHERE title LIKE '%" . $searchVal . "%'";
            }
        }



        // Execute the SQL query
        $sqlQuery = $sqlQuery . " ORDER BY posted_on DESC";
        // echo $sqlQuery;
        $execQuery = $conn->query($sqlQuery);


        $posts = []; // Delare an Empty array for all post


        // Loop through all the available post in the database using a while loop
        while ($postRecord = $execQuery->fetch_assoc()) {
            // Store the user_id from the post record into a variable
            $user_id = $postRecord['user_id'];
            // Call the getUser method to fetch the author using the user_id
            $postRecord['author'] = getUserById($user_id, $conn);
            // Store the category_id from the post record into a variable
            $cat_id = $postRecord['category_id'];
            // Call the getCategory method to fetch the category using the category_id
            $postRecord['cat'] = getCategoryById($cat_id, $conn);
            // Push the post into the posts array
            array_push($posts, $postRecord);
        }

        header('HTTP/1.1 200');
        $response = [
            'status' => 'success',
            'posts' => $posts // Pass the array of posts with the response
        ];

        // Echo the response
        echo json_encode($response);
    }

    // Fetch A Single Post
    if ($action == 'fetch_a_post'){
        $postId = $_POST['post_id'];
        
        $sqlQuery = "SELECT * FROM tbl_posts WHERE id='$postId'";
        // Execute the SQL query
        $execQuery = $conn->query($sqlQuery);

        $post = $execQuery->fetch_assoc();

        // Fetch Author Details
        $user_id = $post['user_id'];
        $post['author'] = getUserById($user_id, $conn);

        // Fetch Category Details
        $cat_id = $post['category_id'];
        $post['cat'] = getCategoryById($cat_id, $conn);

        header('HTTP/1.1 200');
        $response = [
            'status' => 'success',
            'post' => $post // Pass the array of posts with the response
        ];

        // Echo the response
        echo json_encode($response);
    }

    // Check if the request is authorized
    if ($authorized === false) {
        switch ($action) {
            case 'fetch_post':
                    // Leave blank and continue with fetch_post
                break;
            case 'fetch_a_post':
                    // Leave blank and continue with fetch_post
                break;
            
            default:
                // return 401 Error Response
                $response = [
                    'status' => 'failed',
                    'message' => 'Message or reqest not authorized', 
                ];
                // Echo the response
                echo json_encode($response);
                break;
        }
    } else {

        // Else, means the user is authorized. Proceed with any other action
        
        // If action iss save_post, then save the post data to the database
        if ($action == 'save_post'){
            $cat_id = $_POST['category_id'];
            $title = $_POST['title'];
            $body = $_POST['body'];
            $image = null;
            // Call the Image Uploader if image is available on the request
            if (isset($_FILES['ft_image']) && $_FILES['ft_image'] != null) {
                $image = ImageUploader($_FILES['ft_image']);
            }
            
            // Adding the author manually
            $user_id = $authorized_user['id'];

            $data_insertion = $conn->query("INSERT INTO tbl_posts(category_id, title, body, user_id, image) VALUES('$cat_id', '$title', '$body', $user_id, '$image')");

            if ($data_insertion) {
                header('HTTP/1.1 200');
                $response = [
                    'status' => 'success',
                    'message' => 'Post add successfully',
                ];
            }else{
                header('HTTP/1.1 500');
                $response = [
                    'status' => 'failed',
                    'message' => "An error occurred. Please try again. " . $conn->error,
                ];
            }

            echo json_encode($response);
        }
    }









    // Functions
    function ImageUploader($file) {
        $name = $file['name'];
        $tmp_loc = $file['tmp_name'];
        $size = $file['size'];

        $filename =  time() . $name;
        $path = '../public/images/blogs/' . $filename;

        if (move_uploaded_file($tmp_loc, $path)) {
            return $filename;
        } else {
            return null;
        }
    }


    // // Relationships functions
    // function getUserById($id, $conn) {
    //     $userQuery = $conn->query("SELECT * FROM tbl_users WHERE id = '$id'");
    //     $user = $userQuery->fetch_assoc();
    //     return $user;
    // }

    function getCategoryById($id, $conn) {
        $catQuery = $conn->query("SELECT * FROM tbl_category  WHERE id = '$id'");
        $category = $catQuery->fetch_assoc();
        return $category;
    }

    function getCategoryIdByname($categoryName, $conn) {
        $catQuery = $conn->query("SELECT * FROM tbl_category  WHERE category = '$categoryName'");
        $category = $catQuery->fetch_assoc();
        return $category['id'];
    }

?>