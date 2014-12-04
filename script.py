import urllib
import json

url = "http://api.rottentomatoes.com/api/public/v1.0/lists/movies/upcoming.json?page_limit=16&page=1&country=us&apikey=55c4wwmmfea8n9sg2faqqjd6"

avgRevenue = 35000000

response = urllib.urlopen(url)
data = json.loads(response.read())

movies = data["movies"]

for movie in movies:
	print movie["title"]
	ratings = movie["ratings"]
	audience = ratings["audience_score"]
	critic = ratings["critics_score"]
	average = (audience + critic)/2
	print "Audience Score: " + str(audience)
	print "Critic Score: " + str(critic)
	print "Average: " + str(average)
	print "Predicted Revenue: " + str((avgRevenue * average)/100)
	print "\n"
	
	

