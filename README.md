# Meta Box Multirelationship Field

The Meta Box Multirelationship field is not so much a new fieldtype, but a fieldtype to control multiple relationships at once. There are situations where you normally would need to maintain multiple relationships to track.

<img src="/docs/example.png" alt="Example of a multirelationships field"/>

#### Dependencies:

Meta Box plugin
Meta Box Relationships extension

### Situation: The attendance for a class on a certain date by students
On one hand you have a CPT called "attendance" (a certain date a class was given)  and you have a CPT called "student". If you want to keep track of the students that were present, absent and late, AND you also want to be able to see when (all the dates) a certain student was present, absent and late, you would normally add three relationship fields.

The problem however, is that you can select a single student to have a relationship being both 'present', 'absent' and 'late' on the same date because you'll get three different Meta Boxes.

Another 'problem' is that you can keep selecting the same student multiple times from the Meta Box's dropdown, which can get confusing.

### The solution: Add the MB Relationships and the MB Multirelationship Field to maintain them.
All you need to do is add the MB Relationships as usual, but with a small change:

Notice the addition of

	'field' => [ 'save_field' => false ],

on the code to register the MB Relationships normally. This tells MB to not actually save the selected metaboxes when setting the students on an attendance post. This is needed to allow our new field to register all the selected students from our Multirelationship Field.

    add_action( 'mb_relationships_init', function() {
	    MB_Relationships_API::register( [
	        'id'   => 'student_to_attendance_present',
	        'from' => array(
	            'object_type' => 'post',
	            'post_type'		=> 'student',
	            'field' => [ 'save_field' => false ],
	            'meta_box'    => array(
	                'title'       => 'Students Present',
	                'context'	=> 'normal',
	            	),
	        	),
	        'to'   => array(
	            'object_type' => 'post',
	            'post_type'   => 'attendance',
	            'meta_box'    => array(
	                'title'         => 'Was Present',
	                'context'       => 'normal',
	                'empty_message' => 'No properties',
	            	),
	        	)

	    ] );

	    MB_Relationships_API::register( [
	        'id'   => 'student_to_attendance_absent',
	        'from' => array(
	            'object_type' => 'post',
	            'post_type'		=> 'student',
	            'field' => [ 'save_field' => false ],
	            'meta_box'    => array(
	                'title'       => 'Students Absent',
	                'context'	=> 'normal',
	            	),
	        	),
	        'to'   => array(
	            'object_type' => 'post',
	            'post_type'   => 'attendance',
	            'meta_box'    => array(
	                'title'         => 'Was Absent',
	                'context'       => 'normal',
	                'empty_message' => 'No properties',
	            	),
	        	)

	    ] );
	    MB_Relationships_API::register( [
	        'id'   => 'student_to_attendance_late',
	        'from' => array(
	            'object_type' => 'post',
	            'post_type'		=> 'student',
	            'field' => [ 'save_field' => false ],
	            'meta_box'    => array(
	                'title'       => 'Students Late',
	                'context'	=> 'normal',
	            	),
	        	),
	        'to'   => array(
	            'object_type' => 'post',
	            'post_type'   => 'attendance',
	            'meta_box'    => array(
	                'title'         => 'Was Late',
	                'context'       => 'normal',
	                'empty_message' => 'No properties',
	            	),
	        	)

	    ] );

    });

Now that you've added the relationships and the setting to not save the selected students to attendance, we need to add our fieldtype to our metabox:


	add_filter( 'rwmb_meta_boxes', 'attendance_meta_boxes' );

	function attendance_meta_boxes( $meta_boxes ) {
		$prefix = '';

		$meta_boxes[] = array (
			'title' => esc_html__( 'Attendance fields', 'text-domain' ),
			'id' => 'attendance-fields',
			'post_types' => array(
				0 => 'attendance',
			),
			'context' => 'normal',
			'priority' => 'high',
			'fields' => array(
				array (
					'id' => $prefix . 'class_date_time',
					'type' => 'datetime',
					'name' => esc_html__( 'Date Time Picker', 'text-domain' ),
					'desc' => esc_html__( 'Date and Time of the lesson', 'text-domain' ),
					'required' => 1,
				),
				array (
					'id' => $prefix . 'lesson_notes',
					'name' => esc_html__( 'Lesson notes', 'text-domain' ),
					'type' => 'textarea',
				),
				array (
					'id' => 'multirelation',
					'name' => 'Attendance Sheet',
					'type' => 'multirelationship',
					'to'	=> 'student',
					'sanitize_callback' => 'none',
					'autoselect' => 'present',
	           		'options'			=> [
	           									[ 'slug' => 'present' , 'label' => 'Present' , 'relationship_api_id' => 'student_to_attendance_present'  ],
							               		[ 'slug' => 'absent' , 'label' => 'Absent', 'relationship_api_id' => 'student_to_attendance_absent' ],
	           									[ 'slug' => 'late' , 'label' => 'Late' , 'relationship_api_id' => 'student_to_attendance_late' ],
	           								],
					'button_label' => 'Add Student',
				)
			),
		);

		return $meta_boxes;
	}

| Name | Description |
|--|--|
| button_label | Text on the button when adding a new item|
| to | custom post type to get the published rows from (string) |
| autoselect | slug to automatically check when a new item is added (string) |
| options | options to display as radio-buttons when loading or adding an item. each option takes three keys: `slug`, `label`, `relationship_api_id` |
| | `slug` needs to be a unique identifier and is used to register changes to each option (string) |
| | `label` is the label rendered out to the user (string) |
| | `relationship_api_id` is the ID of the relationship as set during registration (string) |
| hide_metaboxes | Adds CSS to hide the default metabox(es) that have no function, but are still showing. optional true / false (boolean)(optional, default: true)


#### Roadmap

Keep the order in which the students are added or order them on title. Currently they are displayed grouped by the relationship (in this example the ones markes as present first, then absent and then late)

#### Changelog

| version | description |
| -- | -- |
| 0.5.1 | removed an option from code-example that wasn't supposed to be in there, renamed the main plugin file, there was a typo. Changed fileheaders to be more descriptive of plugin functionality
| 0.5.0 | first version