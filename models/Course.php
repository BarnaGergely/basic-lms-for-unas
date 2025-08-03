<?php
/**
 * Class Course
 *
 * Represents a course with its product number, role, and title.
 */

class Course {
    public string $product_number;
    public string $role;
    public string $title;

    public function __construct(string $product_number, string $role, string $title = '')
    {
        $this->product_number = $product_number;
        $this->role = $role;
        $this->title =  $title;
    }
}
