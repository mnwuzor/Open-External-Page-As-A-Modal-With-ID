# Open External Page As A Modal With ID
 This is a tutorial on how to open an external page within a modal with corresponding ID of the row record

![image](https://github.com/user-attachments/assets/b0ea80ec-2ff4-487b-bc3c-be3c4b45b41a)

The image above shows the table with rows of records. On the right is the button of which when clicked will show **View Participants**. Now when you click on the **View Participants** it will load the pooup modal as seen below.
![image](https://github.com/user-attachments/assets/765ef3a4-dda1-4732-87e2-5d6ceb6420f3)

On the code side, this is the code behind the blue button that you click to show the **View Participants**
~~~
<a class="dropdown-item" href="viewFiltered?regNumber=<?php echo $row['regNumber']; ?>" data-toggle="modal" data-target="#viewFiltered" name="viewFiltered" ><i class="fa fa-eye" aria-hidden="true"></i> View Participant</a>
~~~
Clearly, you can see the target modal ID **#viewFiltered** that triggers the modal to load
~~~
  <div id="viewFiltered" class="modal fade text-left">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
      </div>
    </div>
  </div>

<script>
$('#viewFiltered').on('hidden.bs.modal', function () {

});
</script>
~~~
Now when the **#viewFiltered** is triggered, it caused the modal to load the page with the corresponding row record as seen below.
![image](https://github.com/user-attachments/assets/765ef3a4-dda1-4732-87e2-5d6ceb6420f3)

To see this in action, kindly watch this YouTube video
https://youtu.be/3tAXA2Bc2zU

Enjoy!!!
