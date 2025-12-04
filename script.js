document.addEventListener('DOMContentLoaded', () => {
    const todoForm = document.getElementById('todo-form');
    const todoInput = document.getElementById('todo-input');
    const todoList = document.getElementById('todo-list');

    // Load todos from localStorage or initialize an empty array
    let todos = JSON.parse(localStorage.getItem('todos')) || [];

    // Function to save todos to localStorage
    const saveTodos = () => {
        localStorage.setItem('todos', JSON.stringify(todos));
    };

    // Function to render todos to the DOM
    const renderTodos = () => {
        // Clear the existing list
        todoList.innerHTML = '';

        // If there are no todos, display a message
        if (todos.length === 0) {
            todoList.innerHTML = '<li>No tasks yet. Add one!</li>';
            return;
        }

        // Loop through todos and create list items
        todos.forEach((todo, index) => {
            const li = document.createElement('li');
            li.textContent = todo.text;
            li.dataset.index = index; // Add index for event handling

            // Add 'completed' class if the todo is completed
            if (todo.completed) {
                li.classList.add('completed');
            }

            // Create a delete button
            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = 'Delete';
            deleteBtn.classList.add('delete-btn');
            deleteBtn.dataset.index = index; // Add index for deletion

            li.appendChild(deleteBtn);
            todoList.appendChild(li);
        });
    };

    // Handle form submission to add a new todo
    todoForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Prevent page reload
        const taskText = todoInput.value.trim();

        if (taskText !== '') {
            // Add new todo to the array
            todos.push({ text: taskText, completed: false });
            saveTodos();
            renderTodos();
            todoInput.value = ''; // Clear the input field
            todoInput.focus();
        }
    });

    // Handle clicks on the todo list for completing or deleting todos
    todoList.addEventListener('click', (e) => {
        const target = e.target;
        const index = target.dataset.index;

        if (index === undefined) return; // Exit if the click is not on a relevant element

        // Check if a delete button was clicked
        if (target.classList.contains('delete-btn')) {
            todos.splice(index, 1); // Remove the todo from the array
        }
        // Check if a list item was clicked to toggle completion
        else if (target.tagName === 'LI') {
            todos[index].completed = !todos[index].completed;
        }

        saveTodos();
        renderTodos();
    });

    // Initial render of todos on page load
    renderTodos();
});
