<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blogs;

class BlogController extends Controller
{
    //
    function addBlog(Request $req)
    {
        $userToken = $req->header('Authorization');
        if($userToken == null || $userToken == '') {
            return response()->json([
                "success" => false,
                "message" => "Invalid token/token not provided"
            ], 401);
        }
        $tokenParts = explode('|', $userToken);

        $username = $tokenParts[0];
        $token = $tokenParts[1];

        $newBlog = new Blogs;
        $newBlog->title = $req->input('title');
        $newBlog->content = $req->input('content');
        $newBlog->author = $username;
        $newBlog->date = $req->input('date');
        $newBlog->save();
        return response()->json([
            "success" => true, 
            "message" => "User Blogs retrieved", 
            "data" => $newBlog
        ]);
    }

    function allBlogs(Request $req) {
        $blogLists = Blogs::get();
        return response()->json([
            "success" => true, 
            "message" => "User Blogs retrieved", 
            "data" => $blogLists
        ]);
    }
    
    function listBlogs(Request $req) {
        
        $userToken = $req->header('Authorization');
        if($userToken == null || $userToken == '') {
            return response()->json([
                "success" => false,
                "message" => "Invalid token/token not provided"
            ], 401);
        }
        $tokenParts = explode('|', $userToken);

        $username = $tokenParts[0];
        $token = $tokenParts[1];
        $blogLists = Blogs::where("author", $username)->get();
        return response()->json([
            "success" => true, 
            "message" => "User Blogs retrieved", 
            "data" => $blogLists
        ]);
    }

    function editBlog(Request $req) {
        $userToken = $req->header('Authorization');
    
        if ($userToken == null || $userToken == '') {
            return response()->json([
                "success" => false,
                "message" => "Invalid token/token not provided"
            ], 401);
        }
    
        $tokenParts = explode('|', $userToken);
    
        if (count($tokenParts) !== 2) {
            return response()->json([
                "success" => false,
                "message" => "Invalid token format"
            ], 401);
        }
    
        $username = $tokenParts[0];
        $token = $tokenParts[1];
    
        $blogId = $req->input('id');
        $newTitle = $req->input('title');
        $newContent = $req->input('content');
        $newDate = $req->input('date');
    
        $blogToEdit = Blogs::where("author", $username)
                          ->where("id", $blogId)
                          ->first();
    
        if ($blogToEdit) {
            if ($newTitle) {
                $blogToEdit->title = $newTitle;
            }
            if ($newContent) {
                $blogToEdit->content = $newContent; // Fixed: Update content instead of title
            }
            if ($newDate) {
                $blogToEdit->date = $newDate; // Fixed: Update date field
            }
    
            $blogToEdit->save();
    
            return response()->json([
                "success" => true,
                'message' => 'Blog edit successful.',
                "data" => $blogToEdit
            ], 200);
        } else {
            return response()->json([
                "success" => false,
                'message' => 'Blog not found.',
                "data" => null
            ], 404);
        }
    }

    function deleteBlog(Request $req) {
        $userToken = $req->header('Authorization');
    
        if ($userToken == null || $userToken == '') {
            return response()->json([
                "success" => false,
                "message" => "Invalid token/token not provided"
            ], 401);
        }
    
        $tokenParts = explode('|', $userToken);
    
        if (count($tokenParts) !== 2) {
            return response()->json([
                "success" => false,
                "message" => "Invalid token format"
            ], 401);
        }
    
        $username = $tokenParts[0];
        $token = $tokenParts[1];
        $id = $req->input("id");

        $deleted = Blogs::where('author', $username)
            ->where("id", $id)
            ->delete();

        if ($deleted > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Blogs deleted successfully.'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No blogs found to delete'
            ], 404);
        }
    }
}
