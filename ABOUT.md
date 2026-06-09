# ABOUT.md

## Why this role

I am looking to deepen my expertise in building production-ready AI applications and deploying them. Working at a YC backed startup would be the best place to gain this experience. The challenge was enough for me to understand the type of work that is happening in the field and it arised my interest to look into the places where AI is a great fit for solving real world problems.  

## How you work with AI tools

AI tools are almost building the entire project for me, especially frontends. For this challenge, it wrote the whole code for step B and I didn't modify anything in it, I just told cursor to follow the Plan and ensure it has the write information from the other files.

## Your last project (structured — this is the pre-filter)

I am talking about my project JobCrew (detials can be found in my portfolio https://suhaibjbara.online)

- **One ambiguity** you faced and how you resolved it: people could just add whatever role they want and get a tailored resume for it ignoring the seniority level of the position. I resolved this by adding a validator agent, it works both ways:
    1 - first by checking the seniority level choosen by the user in our UI and the job description
    2- Users may choose any seniority, so our agent calculates the user's total experience from thier resume
- **One tradeoff** you made and why: Tradeoffs were mostly about cost, I am building this to gain experience and I don't have much to pay for it, I was able to handle this by keeping everything in the AWS free tier plan. Also, I was deciding to use local LLMs but then I changed to bedrock because it is more reliable and hopefully it will not break the bank.
- **One mistake** you made and what you changed: Outputs would be generated onces and sent to the frontend, if they were lost then a whole new run should be done to get the output again and that is if they weren't lost again. Then I added a session control where it ensures that the output would never be lost by storing the output in the agent memory and incase it didn't show we can call it again via an api using the session id to show the output agian.
- **One review comment** that made you change your mind: N/A I built it solo and it is yet to get reviews from the public.

## Anything you'd improve about THIS challenge or our CLAUDE.md

I enjoyed this challenge a lot, it showed me the type of AI work happening in the real world. It is clear and easy to follow, I don't think it needs improvement
