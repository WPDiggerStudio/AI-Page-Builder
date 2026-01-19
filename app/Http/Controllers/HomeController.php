<?php

namespace BraCalculator\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Home Controller
 *
 * Handles home page and dashboard requests.
 *
 * @package BraCalculator\App\Http\Controllers
 */
class HomeController {
    /**
     * Display the home page.
     *
     * @return Response
     */
    public function index(): Response {
        return response()->view('home', [
            'title' => 'Welcome to WP Jarvis',
            'description' => 'A Laravel-first application framework for WordPress.',
        ]);
    }

	/**
	 * Display a page by slug.
	 *
	 * @param string $slug The page slug
	 *
	 * @return Response
	 * @throws \Exception
	 */
    public function page(string $slug): Response {
        // Get page from WordPress
        $page = get_page_by_path($slug);

        if (!$page) {
            abort(404, 'Page not found');
        }

        return response()->view('page', [
            'page' => $page,
            'title' => $page->post_title,
        ]);
    }

    /**
     * Display the dashboard.
     *
     * @return Response
     */
    public function dashboard(): Response {
        return response()->view('dashboard', [
            'title' => 'Dashboard',
            'user' => wp_get_current_user(),
        ]);
    }
}
